<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Model\Behavior;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;
use Elastic\ActivityLogger\Model\Table\ActivityLogsTable;
use Psr\Log\LogLevel;

/**
 * Logger behavior
 *
 * example:
 *
 * in Table (eg. CommentsTable)
 * <pre><code>
 * public function initialize(array $config)
 * {
 *      $this->addBehavior('Elastic/ActivityLogger.Logger', [
 *          'scope' => [
 *              'Authors',
 *              'Articles',
 *              'PluginName.Users',
 *          ],
 *      ]);
 * }
 * </code></pre>
 *
 * set Scope/Issuer
 * <pre><code>
 * $commentsTable->logScope([$article, $author])->logIssuer($user);
 * </code></pre>
 */
class LoggerBehavior extends Behavior
{
    use LocatorAwareTrait;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'logModel' => 'Elastic/ActivityLogger.ActivityLogs',
        'logModelAlias' => 'ActivityLogs',
        'scope' => [],
        'systemScope' => true,
        'scopeMap' => [],
        'implementedMethods' => [
            'activityLog' => 'activityLog',
            'getLogIssuer' => 'getLogIssuer',
            'getLogMessageBuilder' => 'getLogMessageBuilder',
            'getLogScope' => 'getLogScope',
            'setLogIssuer' => 'setLogIssuer',
            'setLogMessageBuilder' => 'setLogMessageBuilder',
            'setLogMessage' => 'setLogMessage',
            'setLogScope' => 'setLogScope',
            'resetLogScope' => 'resetLogScope',
        ],
    ];

    /**
     * @return array
     */
    public function implementedEvents(): array
    {
        return parent::implementedEvents() + [
                'Model.initialize' => 'afterInit',
            ];
    }

    /**
     * Run at after Table.initialize event
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function afterInit(): void
    {
        $scope = $this->getConfig('scope');

        if (empty($scope)) {
            $scope = [$this->_table->getRegistryAlias()];
        }

        if ($this->getConfig('systemScope')) {
            $namespace = $this->getConfig('systemScope') === true
                ? Configure::read('App.namespace')
                : $this->getConfig('systemScope');
            $scope['\\' . $namespace] = true;
        }

        $this->setConfig('scope', $scope, false);
        $this->setConfig('originalScope', $scope);
    }

    /**
     * @param \Cake\Event\Event $event the event
     * @param \Cake\Datasource\EntityInterface $entity saving entity
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterSave(Event $event, EntityInterface $entity): void
    {
        $entity->setSource($this->_table->getRegistryAlias()); // for entity of belongsToMany intermediate table
        $log = $this->buildLog($entity, $this->getConfig('issuer'));
        $log->action = $entity->isNew() ? ActivityLog::ACTION_CREATE : ActivityLog::ACTION_UPDATE;
        $log->data = $this->getDirtyData($entity);
        $log->message = $this->buildMessage($log, $entity, $this->getConfig('issuer'));

        $logs = $this->duplicateLogByScope($this->getConfig('scope'), $log, $entity);
        $this->saveLogs($logs);
    }

    /**
     * @param \Cake\Event\Event $event the event
     * @param \Cake\Datasource\EntityInterface $entity deleted entity
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterDelete(Event $event, EntityInterface $entity): void
    {
        $entity->setSource($this->_table->getRegistryAlias()); // for entity of belongsToMany intermediate table
        $log = $this->buildLog($entity, $this->getConfig('issuer'));
        $log->action = ActivityLog::ACTION_DELETE;
        $log->data = $this->getData($entity);
        $log->message = $this->buildMessage($log, $entity, $this->getConfig('issuer'));

        $logs = $this->duplicateLogByScope($this->getConfig('scope'), $log, $entity);

        $this->saveLogs($logs);
    }

    /**
     * Get the log scope
     *
     * @return array
     */
    public function getLogScope(): array
    {
        return $this->getConfig('scope');
    }

    /**
     * Set the log scope
     *
     * @param \Cake\Datasource\EntityInterface|array<string>|array<\Cake\Datasource\EntityInterface>|string $args the log scope
     * @return \Cake\ORM\Table|self
     */
    public function setLogScope(string|array|EntityInterface $args): Table|self
    {
        // setter
        if (!is_array($args)) {
            $args = [$args];
        }

        $scope = [];
        foreach ($args as $key => $val) {
            if (is_int($key) && is_string($val)) {
                // [0 => 'Scope']
                $scope[$val] = true;
            } else {
                $scope[$key] = $val;
            }
        }
        $this->setConfig('scope', $scope);

        return $this->_table;
    }

    /**
     * Get the log issuer
     *
     * @return \Cake\Datasource\EntityInterface|null
     */
    public function getLogIssuer(): ?EntityInterface
    {
        return $this->getConfig('issuer');
    }

    /**
     * Set the log issuer
     *
     * @param \Cake\Datasource\EntityInterface $issuer the issuer
     * @return \Cake\ORM\Table|self
     */
    public function setLogIssuer(EntityInterface $issuer): Table|self
    {
        $this->setConfig('issuer', $issuer);

        // set issuer to scope, if the scopes contain the issuer's model
        [$issuerModel] = $this->buildObjectParameter($this->getConfig('issuer'));
        if (array_key_exists($issuerModel, $this->getConfig('scope'))) {
            $this->setLogScope($issuer);
        }

        return $this->_table;
    }

    /**
     * Get the log message builder
     *
     * @return callable|null
     */
    public function getLogMessageBuilder(): ?callable
    {
        return $this->getConfig('messageBuilder');
    }

    /**
     * Set the log message builder
     *
     * @param callable|null $handler the message build method
     * @return \Cake\ORM\Table|self
     */
    public function setLogMessageBuilder(?callable $handler = null): Table|self
    {
        $this->setConfig('messageBuilder', $handler);

        return $this->_table;
    }

    /**
     * Set a log message
     *
     * @param string $message the message
     * @param bool $persist if true, keeps the message.
     * @return \Cake\ORM\Table|self
     */
    public function setLogMessage(string $message, bool $persist = false): Table|self
    {
        $this->setLogMessageBuilder(function () use ($message, $persist) {
            if (!$persist) {
                $this->setLogMessageBuilder(null);
            }

            return $message;
        });

        return $this->_table;
    }

    /**
     * Record a custom log
     *
     * @param string $level log level
     * @param string $message log message
     * @param array $context context data
     * [
     *   'object' => Entity,
     *   'issuer' => Entity,
     *   'scope' => Entity[],
     *   'action' => string,
     *   'data' => array,
     * ]
     * @return array<\Elastic\ActivityLogger\Model\Entity\ActivityLog>
     */
    public function activityLog(string $level, string $message, array $context = []): array
    {
        $entity = $context['object'] ?? null;
        $issuer = $context['issuer'] ?? $this->getConfig('issuer');
        $scope = !empty($context['scope'])
            ? $this->buildScope($context['scope'])
            : $this->getConfig('scope');

        $log = $this->buildLog($entity, $issuer);
        $log->set([
            'action' => $context['action'] ?? ActivityLog::ACTION_RUNTIME,
            'data' => $context['data'] ?? $this->getData($entity),
            'level' => $level,
            'message' => $message,
        ]);

        $log->message = $this->buildMessage($log, $entity, $issuer);

        // set issuer to scope, if the scopes contain the issuer's model
        if (!empty($log->issuer_id) && array_key_exists($log->issuer_model, $this->getConfig('scope'))) {
            $scope[$log->issuer_model] = $log->issuer_id;
        }

        $logs = $this->duplicateLogByScope($scope, $log, $entity);

        $this->saveLogs($logs);

        return $logs;
    }

    /**
     * Activity log finder
     *
     * $table->find('activity', ['scope' => $entity])
     *
     * @param \Cake\ORM\Query $query the query
     * @param array $options find options
     * @return \Cake\ORM\Query
     * @noinspection PhpUnusedParameterInspection
     */
    public function findActivity(Query $query, array $options): Query
    {
        $logTable = $this->getLogTable();
        $logQuery = $logTable->find();

        $where = [$logTable->aliasField('scope_model') => $this->_table->getRegistryAlias()];

        if (isset($options['scope']) && $options['scope'] instanceof Entity) {
            [$scopeModel, $scopeId] = $this->buildObjectParameter($options['scope']);
            $where[$logTable->aliasField('scope_model')] = $scopeModel;
            $where[$logTable->aliasField('scope_id')] = $scopeId;
        }

        $logQuery->where($where)->orderBy([$logTable->aliasField('id') => 'desc']);

        return $logQuery;
    }

    /**
     * Build log entity
     *
     * @param \Cake\Datasource\EntityInterface|null $entity the entity
     * @param \Cake\Datasource\EntityInterface|null $issuer the issuer
     * @return \Elastic\ActivityLogger\Model\Entity\ActivityLog|\Cake\Datasource\EntityInterface
     */
    private function buildLog(
        ?EntityInterface $entity = null,
        ?EntityInterface $issuer = null
    ): ActivityLog|EntityInterface {
        [$issuer_model, $issuer_id] = $this->buildObjectParameter($issuer);
        [$object_model, $object_id] = $this->buildObjectParameter($entity);

        $level = LogLevel::INFO;
        $message = '';

        return $this->getLogTable()
            ->newEntity(compact(
                'issuer_model',
                'issuer_id',
                'object_model',
                'object_id',
                'level',
                'message'
            ));
    }

    /**
     * Build parameter from an entity
     *
     * @param \Cake\Datasource\EntityInterface|null $object the object
     * @return array [object_model, object_id]
     * @see \Elastic\ActivityLogger\Model\Table\ActivityLogsTable::buildObjectParameter()
     */
    private function buildObjectParameter(?EntityInterface $object): array
    {
        return $this->getLogTable()->buildObjectParameter($object);
    }

    /**
     * Build log message
     *
     * @param \Elastic\ActivityLogger\Model\Entity\ActivityLog|\Cake\Datasource\EntityInterface $log log object
     * @param \Cake\Datasource\EntityInterface|null $entity saved entity
     * @param \Cake\Datasource\EntityInterface|null $issuer issuer
     * @return string
     */
    private function buildMessage(
        ActivityLog|EntityInterface $log,
        ?EntityInterface $entity = null,
        ?EntityInterface $issuer = null
    ): string {
        if (!is_callable($this->getConfig('messageBuilder'))) {
            return $log->message;
        }
        $context = ['object' => $entity, 'issuer' => $issuer];

        return call_user_func($this->getConfig('messageBuilder'), $log, $context);
    }

    /**
     * Duplicate the log by scopes
     *
     * @param array $scope target scope
     * @param \Elastic\ActivityLogger\Model\Entity\ActivityLog $log duplicate logs
     * @param \Cake\Datasource\EntityInterface|null $entity the entity
     * @return array<\Elastic\ActivityLogger\Model\Entity\ActivityLog>
     */
    private function duplicateLogByScope(array $scope, ActivityLog $log, ?EntityInterface $entity = null): array
    {
        $logs = [];

        if ($entity !== null) {
            // Auto mapping from fields
            foreach ($this->getConfig('scopeMap') as $field => $scopeModel) {
                if (array_key_exists($scopeModel, $scope) && !empty($entity->get($field))) {
                    $scope[$scopeModel] = $entity->get($field);
                }
            }
        }

        foreach ($scope as $scopeModel => $scopeId) {
            if ($entity !== null && $scopeModel === $this->_table->getRegistryAlias()) {
                // Set the entity id to scope, if own scope
                $scopeId = $this->getLogTable()->getScopeId($this->_table, $entity);
            }
            if (empty($scopeId)) {
                continue;
            }
            $new = $this->getLogTable()->newEntity($log->toArray() + [
                    'scope_model' => $scopeModel,
                    'scope_id' => $scopeId,
                ]);
            $logs[] = $new;
        }

        return $logs;
    }

    /**
     * @param iterable<\Elastic\ActivityLogger\Model\Entity\ActivityLog> $logs save logs
     * @return void
     */
    private function saveLogs(iterable $logs): void
    {
        /** @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $logTable */
        $logTable = $this->getLogTable();
        foreach ($logs as $log) {
            $logTable->save($log, ['atomic' => false]);
        }
    }

    /**
     * @return \Elastic\ActivityLogger\Model\Table\ActivityLogsTable|\Cake\ORM\Table
     */
    private function getLogTable(): ActivityLogsTable|Table
    {
        return $this->fetchTable($this->getConfig('logModelAlias'), [
            'className' => $this->getConfig('logModel'),
        ]);
    }

    /**
     * Get modified values from the entity
     *
     * - exclude hidden values
     *
     * @param \Cake\Datasource\EntityInterface|null $entity the entity
     * @return array|null
     */
    private function getDirtyData(?EntityInterface $entity = null): ?array
    {
        if ($entity === null) {
            return null;
        }

        return $entity->extract($entity->getVisible(), true);
    }

    /**
     * Get values from the entity
     *
     * - exclude hidden values
     *
     * @param \Cake\Datasource\EntityInterface|null $entity the entity
     * @return array|null
     */
    private function getData(?EntityInterface $entity = null): ?array
    {
        if ($entity === null) {
            return null;
        }

        return $entity->extract($entity->getVisible());
    }

    /**
     * Reset log scope
     *
     * @return \Cake\ORM\Table|self
     */
    public function resetLogScope(): Table|self
    {
        $this->setConfig('scope', $this->getConfig('originalScope'), false);

        return $this->_table;
    }

    /**
     * @param array|string $key config key
     * @param mixed $value set value
     * @param bool $merge override
     * @return void
     */
    protected function _configWrite(array|string $key, mixed $value, string|bool $merge = false): void
    {
        if ($key === 'scope') {
            $value = $this->buildScope($value);
        }
        parent::_configWrite($key, $value, $merge);
    }

    /**
     * scope設定
     *
     * @param \Cake\Datasource\EntityInterface|array<string>|array<\Cake\Datasource\EntityInterface>|string $value the scope
     * @return array ['Scope.Key' => 'scope id', ...]
     */
    private function buildScope(string|array|EntityInterface $value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $new = [];
        foreach ($value as $key => $arg) {
            if (is_string($key)) {
                $new[$key] = $arg;
            } elseif (is_string($arg)) {
                $new[$arg] = null;
            } elseif ($arg instanceof EntityInterface) {
                $table = $this->fetchTable($arg->getSource());
                $scopeId = $this->getLogTable()->getScopeId($table, $arg);
                $new[$table->getRegistryAlias()] = $scopeId;
            }
        }

        return $new;
    }
}
