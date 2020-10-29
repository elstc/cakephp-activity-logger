<?php
/** @noinspection PhpUnusedLocalVariableInspection */
/** @noinspection PhpUnusedParameterInspection */

namespace Elastic\ActivityLogger\Model\Behavior;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
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
 *              'Elastic/ActivityLogger.Authors',
 *              'Elastic/ActivityLogger.Articles',
 *              'Elastic/ActivityLogger.Users',
 *          ],
 *      ]);
 * }
 * </code></pre>
 *
 * set Scope/Issuer
 * <pre><code>
 * $commentsTable->setLogScope([$artice, $author])->setLogScope($user);
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
    protected $_defaultConfig = [
        'logModel' => 'Elastic/ActivityLogger.ActivityLogs',
        'logModelAlias' => 'ActivityLogs',
        'scope' => [],
        'systemScope' => true,
        'scopeMap' => [],
    ];

    /**
     * @return array
     */
    public function implementedEvents()
    {
        return parent::implementedEvents() + [
                'Model.initialize' => 'afterInit',
            ];
    }

    /**
     * Run at after Table.initialize event
     *
     * @param Event $event the event
     * @return void
     */
    public function afterInit(Event $event)
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
     * @param Event $event the event
     * @param Entity $entity saving entity
     * @param ArrayObject $options save options
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
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
     * @param Event $event the event
     * @param Entity $entity deleted entity
     * @param ArrayObject $options delete options
     * @return void
     */
    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
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
    public function getLogScope()
    {
        return $this->getConfig('scope');
    }

    /**
     * Set the log scope
     *
     * @param mixed $args if $args = false then reset the scope
     * @return void
     */
    public function setLogScope($args)
    {
        if ($args === false) {
            // reset
            $this->setConfig('scope', $this->getConfig('originalScope'), false);
        } else {
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
        }
    }

    /**
     * Set or get the scope
     *
     * @param mixed $args if $args = false then reset the scope
     * @return Table|array
     * @deprecated 1.2.0 use setLogScope()/getLogScope() instead.
     */
    public function logScope($args = null)
    {
        if ($args === null) {
            // getter
            return $this->getLogScope();
        }

        $this->setLogScope($args);

        return $this->_table;
    }

    /**
     * Get the log issuer
     *
     * @return array
     */
    public function getLogIssuer()
    {
        return $this->getConfig('issuer');
    }

    /**
     * Set the log issuer
     *
     * @param Entity $issuer the issuer
     * @return void
     */
    public function setLogIssuer(Entity $issuer)
    {
        $this->setConfig('issuer', $issuer);

        // set issuer to scope, if the scopes contain the issuer's model
        list($issuerModel, $issuerId) = $this->buildObjectParameter($this->getConfig('issuer'));
        if (array_key_exists($issuerModel, $this->getConfig('scope'))) {
            $this->setLogScope($issuer);
        }
    }

    /**
     * Set or get the log issuer
     *
     * @param Entity $issuer the issuer
     * @return Table
     * @deprecated 1.2.0 use setLogIssuer()/getLogIssuer() instead.
     */
    public function logIssuer(Entity $issuer = null)
    {
        if ($issuer === null) {
            // getter
            return $this->getConfig('issuer');
        }
        // setter
        $this->setConfig('issuer', $issuer);

        // set issuer to scope, if the scopes contain the issuer's model
        list($issuerModel, $issuerId) = $this->buildObjectParameter($this->getConfig('issuer'));
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
    public function getLogMessageBuilder()
    {
        return $this->getConfig('messageBuilder');
    }

    /**
     * Set the log message builder
     *
     * @param callable $handler the message build method
     * @return void
     */
    public function setLogMessageBuilder(callable $handler = null)
    {
        $this->setConfig('messageBuilder', $handler);
    }

    /**
     * Set or get the log message builder
     *
     * @param callable $handler the message build method
     * @return callable|void
     * @deprecated 1.2.0 use setLogMessageBuilder()/getLogMessageBuilder() instead.
     */
    public function logMessageBuilder(callable $handler = null)
    {
        if ($handler === null) {
            // getter
            return $this->getConfig('messageBuilder');
        }
        // setter
        $this->setConfig('messageBuilder', $handler);
    }

    /**
     * Set a log message
     *
     * @param string $message the message
     * @param bool $persist if true, keeps the message.
     * @return void
     */
    public function setLogMessage($message, $persist = false)
    {
        $this->setLogMessageBuilder(function () use ($message, $persist) {
            if (!$persist) {
                $this->setLogMessageBuilder(null);
            }

            return $message;
        });
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
     * @return ActivityLog[]|array
     */
    public function activityLog($level, $message, array $context = [])
    {
        $entity = !empty($context['object']) ? $context['object'] : null;
        $issuer = !empty($context['issuer']) ? $context['issuer'] : $this->getConfig('issuer');
        $scope = !empty($context['scope']) ? $this->buildScope($context['scope']) : $this->getConfig('scope');

        $log = $this->buildLog($entity, $issuer);
        $log->action = isset($context['action']) ? $context['action'] : ActivityLog::ACTION_RUNTIME;
        $log->data = isset($context['data']) ? $context['data'] : $this->getData($entity);

        $log->level = $level;
        $log->message = $message;
        /** @noinspection SuspiciousAssignmentsInspection */
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
     * @return ActivityLog[]|array
     * @deprecated 1.2.0 use activityLog() instead.
     */
    public function log($level, $message, array $context = [])
    {
        return $this->activityLog($level, $message, $context);
    }

    /**
     * Activity log finder
     *
     * $table->find('activity', ['scope' => $entity])
     *
     * @param Query $query the query
     * @param array $options find options
     * @return Query
     */
    public function findActivity(Query $query, array $options)
    {
        $logTable = $this->getLogTable();
        $logQuery = $logTable->find();

        $where = [$logTable->aliasField('scope_model') => $this->_table->getRegistryAlias()];

        if (isset($options['scope']) && $options['scope'] instanceof Entity) {
            list($scopeModel, $scopeId) = $this->buildObjectParameter($options['scope']);
            $where[$logTable->aliasField('scope_model')] = $scopeModel;
            $where[$logTable->aliasField('scope_id')] = $scopeId;
        }

        $logQuery->where($where)->order([$logTable->aliasField('id') => 'desc']);

        return $logQuery;
    }

    /**
     * Build log entity
     *
     * @param EntityInterface $entity the entity
     * @param EntityInterface $issuer the issuer
     * @return ActivityLog
     */
    private function buildLog(EntityInterface $entity = null, EntityInterface $issuer = null)
    {
        list($issuer_model, $issuer_id) = $this->buildObjectParameter($issuer);
        list($object_model, $object_id) = $this->buildObjectParameter($entity);

        $level = LogLevel::INFO;
        $message = '';

        $logTable = $this->getLogTable();
        /* @var ActivityLogsTable $logTable */
        $log = $logTable->newEntity(compact('issuer_model', 'issuer_id', 'object_model', 'object_id', 'level', 'message'));

        return $log;
    }

    /**
     * Build parameter from an entity
     *
     * @param EntityInterface $object the object
     * @return array [object_model, object_id]
     * @see \Elastic\ActivityLogger\Model\Table\ActivityLogsTable::buildObjectParameter()
     */
    private function buildObjectParameter($object)
    {
        return $this->getLogTable()->buildObjectParameter($object);
    }

    /**
     * Build log message
     *
     * @param ActivityLog $log log object
     * @param EntityInterface $entity saved entity
     * @param EntityInterface $issuer issuer
     * @return string
     */
    private function buildMessage($log, $entity = null, $issuer = null)
    {
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
     * @param ActivityLog $log duplicate logs
     * @param EntityInterface $entity the entity
     * @return ActivityLog[]|array
     */
    private function duplicateLogByScope(array $scope, ActivityLog $log, EntityInterface $entity = null)
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
     *
     * @param ActivityLog[] $logs save logs
     * @return void
     */
    private function saveLogs($logs)
    {
        $logTable = $this->getLogTable();
        /* @var ActivityLogsTable $logTable */
        foreach ($logs as $log) {
            $logTable->save($log, ['atomic' => false]);
        }
    }

    /**
     *
     * @return ActivityLogsTable|Table
     */
    private function getLogTable()
    {
        if (method_exists($this, 'getTableLocator')) {
            $tableLocator = $this->getTableLocator();
        } else {
            $tableLocator = $this->tableLocator();
        }

        return $tableLocator->get($this->getConfig('logModelAlias'), [
            'className' => $this->getConfig('logModel'),
        ]);
    }

    /**
     * Get modified values from the entity
     *
     * - exclude hidden values
     *
     * @param EntityInterface $entity the entity
     * @return array
     */
    private function getDirtyData(EntityInterface $entity = null)
    {
        if ($entity === null) {
            return null;
        }

        if (method_exists($entity, 'getVisible')) {
            // CakePHP >= 3.8
            return $entity->extract($entity->getVisible(), true);
        }

        // CakePHP < 3.8
        return $entity->extract($entity->visibleProperties(), true);
    }

    /**
     * Get values from the entity
     *
     * - exclude hidden values
     *
     * @param EntityInterface $entity the entity
     * @return array
     */
    private function getData(EntityInterface $entity = null)
    {
        if ($entity === null) {
            return null;
        }

        if (method_exists($entity, 'getVisible')) {
            // CakePHP >= 3.8
            return $entity->extract($entity->getVisible());
        }

        // CakePHP < 3.8
        return $entity->extract($entity->visibleProperties());
    }

    /**
     * @param array|string $key config key
     * @param mixed $value set value
     * @param bool $merge override
     * @return void
     */
    protected function _configWrite($key, $value, $merge = false)
    {
        if ($key === 'scope') {
            $value = $this->buildScope($value);
        }
        parent::_configWrite($key, $value, $merge);
    }

    /**
     * scope設定
     *
     * @param mixed $value the scope
     * @return array
     */
    private function buildScope($value)
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
            } elseif ($arg instanceof Entity) {
                $table = TableRegistry::get($arg->getSource());
                $scopeId = $this->getLogTable()->getScopeId($table, $arg);
                $new[$table->getRegistryAlias()] = $scopeId;
            }
        }

        return $new;
    }
}
