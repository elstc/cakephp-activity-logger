<?php

namespace Elastic\ActivityLogger\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Utility\Hash;
use \ArrayObject;
use Psr\Log\LogLevel;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;
use Elastic\ActivityLogger\Model\Table\ActivityLogsTable;

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
 * $commentsTable->logScope([$artice, $author])->logIssuer($user);
 * </code></pre>
 */
class LoggerBehavior extends Behavior
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'logTable' => 'Elastic/ActivityLogger.ActivityLogs',
        'scope'    => [],
    ];

    public function implementedEvents()
    {
        return parent::implementedEvents() + [
            'Model.initialize' => 'afterInit',
        ];
    }

    public function implementedMethods()
    {
        return parent::implementedMethods() + [
            'activityLog' => 'log',
        ];
    }

    /**
     * Table.initializeの後に実行
     *
     * @param Event $event
     */
    public function afterInit(Event $event)
    {
        $scope = $this->config('scope');
        if (empty($scope)) {
            $scope = [$this->_table->registryAlias()];
        }
        $this->config('scope', $scope, false);
        $this->config('originalScope', $scope);
    }

    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $log = $this->buildLog($entity, $this->config('issuer'));
        $log->action = $entity->isNew() ? ActivityLog::ACTION_CREATE : ActivityLog::ACTION_UPDATE;
        $log->data = $this->getDirtyData($entity);

        $logs = $this->duplicateLogByScope($this->config('scope'), $log, $entity);

        $this->saveLogs($logs);
    }

    public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $log = $this->buildLog($entity, $this->config('issuer'));
        $log->action = ActivityLog::ACTION_DELETE;
        $log->data = $this->getData($entity);

        $logs = $this->duplicateLogByScope($this->config('scope'), $log, $entity);

        $this->saveLogs($logs);
    }

    /**
     * ログを作成
     *
     * @param EntityInterface $entity
     * @param EntityInterface $issuer
     * @return ActivityLog
     */
    private function buildLog(EntityInterface $entity, EntityInterface $issuer = null)
    {
        list($issuer_model, $issuer_id) = $this->buildIssuerParameter($issuer);
        list($object_model, $object_id) = $this->buildObjectParameter($entity);

        $level = LogLevel::INFO;
        $message = '';

        $logTable = $this->getLogTable();
        /* @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $logTable */
        $log = $logTable->newEntity(compact('issuer_model', 'issuer_id', 'object_model', 'object_id', 'level', 'message'));
        return $log;
    }

    /**
     * ログ発行者（操作者）の取得
     *
     * @param \Cake\ORM\Entity $issuer
     * @return array
     */
    private function buildIssuerParameter($issuer)
    {
        $issuerModel = null;
        $issuerId = null;
        if ($issuer && $issuer instanceof \Cake\ORM\Entity) {
            $issuerTable = TableRegistry::get($issuer->source());
            $issuerModel = $issuerTable->registryAlias();
            $issuerId = $issuer->get($issuerTable->primaryKey());
        }
        return [$issuerModel, $issuerId];
    }

    /**
     * ログ発行者（操作者）の取得
     *
     * @param \Cake\ORM\Entity $object
     * @return array
     */
    private function buildObjectParameter($object)
    {
        $objectModel = null;
        $objectId = null;
        if ($object && $object instanceof \Cake\ORM\Entity) {
            $objectTable = TableRegistry::get($object->source());
            $objectModel = $objectTable->registryAlias();
            $objectId = $object->get($objectTable->primaryKey());
        }
        return [$objectModel, $objectId];
    }

    /**
     * ログデータをスコープに応じて複製
     *
     * @param array $scope
     * @param ActivityLog $log
     * @param EntityInterface $entity
     * @return ActivityLog[]
     */
    private function duplicateLogByScope(array $scope, ActivityLog $log, EntityInterface $entity = null)
    {
        $logs = [];
        foreach ($scope as $scopeModel => $scopeId) {
            if (!empty($entity) && $scopeModel === $this->_table->registryAlias()) {
                // モデル自身に対する更新の場合は、entityのidをセットする
                $scopeId = $entity->get($this->_table->primaryKey());
            }
            if (empty($scopeId)) {
                continue;
            }
            $new = $this->getLogTable()->newEntity($log->toArray() + [
                'scope_model' => $scopeModel,
                'scope_id'    => $scopeId,
            ]);
            $logs[] = $new;
        }
        return $logs;
    }

    /**
     *
     * @param ActivityLog[] $logs
     */
    private function saveLogs($logs)
    {
        $logTable = $this->getLogTable();
        /* @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $logTable */
        $logTable->connection()->useSavePoints(true);
        return $logTable->connection()->transactional(function () use ($logTable, $logs) {
            foreach ($logs as $log) {
                $logTable->save($log, ['atomic' => false]);
            }
        });
    }

    /**
     *
     * @return \Elastic\ActivityLogger\Model\Table\ActivityLogsTable
     */
    private function getLogTable()
    {
        return TableRegistry::get('ActivityLog', [
            'className' => $this->config('logTable'),
        ]);
    }

    /**
     * エンティティ変更値の取得
     *
     * hiddenに設定されたものは除く
     *
     * @param EntityInterface $entity
     * @return array
     */
    private function getDirtyData(EntityInterface $entity)
    {
        if (empty($entity)) {
            return null;
        }
        return $entity->extract($entity->visibleProperties(), true);
    }

    /**
     * エンティティ値の取得
     *
     * hiddenに設定されたものは除く
     *
     * @param EntityInterface $entity
     * @return array
     */
    private function getData(EntityInterface $entity)
    {
        if (empty($entity)) {
            return null;
        }
        return $entity->extract($entity->visibleProperties());
    }

    protected function _configWrite($key, $value, $merge = false)
    {
        if ($key === 'scope') {
            $value = $this->__configScope($value);
        }
        parent::_configWrite($key, $value, $merge);
    }

    /**
     * scope設定
     *
     * @param mixed $value
     * @return array
     */
    private function __configScope($value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $new = [];
        foreach ($value as $arg) {
            if (is_string($arg)) {
                $new[$arg] = null;
            } elseif ($arg instanceof \Cake\ORM\Entity) {
                $table = TableRegistry::get($arg->source());
                $new[$table->registryAlias()] = $arg->get($table->primaryKey());
            }
        }

        return $new;
    }

    /**
     * ログスコープの設定
     *
     * @param mixed $args if $args === false リセット
     * @return Table
     */
    public function logScope($args = null)
    {
        if (is_null($args)) {
            // getter
            return $this->config('scope');
        }

        if ($args === false) {
            // reset
            $this->config('scope', $this->config('originalScope'), false);
        } else {
            // setter
            $this->config('scope', $args);
        }
        return $this->_table;
    }

    /**
     * ログ発行者の設定
     *
     * @param \Cake\ORM\Entity $issuer
     * @return Table
     */
    public function logIssuer(\Cake\ORM\Entity $issuer = null)
    {
        if (is_null($issuer)) {
            // getter
            return $this->config('issuer');
        }
        // setter
        $this->config('issuer', $issuer);

        // scopeに含む場合、併せてscopeにセット
        list($issuerModel, $issuerId) = $this->buildIssuerParameter($this->config('issuer'));
        if (in_array($issuerModel, array_keys($this->config('scope')))) {
            $this->logScope($issuer);
        }
        return $this->_table;
    }

    /**
     * カスタムログの記述
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = [])
    {
        $entity = !empty($context['object']) ? $context['object'] : null;
        $issuer = !empty($context['issuer']) ? $context['issuer'] : $this->config('issuer');
        $scope = !empty($context['scope']) ? $this->__configScope($context['scope']) : $this->config('scope');

        $log = $this->buildLog($entity, $issuer);
        $log->action = isset($context['action']) ? $context['action'] : ActivityLog::ACTION_RUNTIME;
        $log->data = isset($context['data']) ? $context['data'] : $this->getData($entity);

        $log->level = $level;
        $log->message = $message;

        // issuerをscopeに含む場合、併せてscopeにセット
        if (!empty($log->issuer_id) && in_array($log->issuer_model, array_keys($this->config('scope')))) {
            $scope[$log->issuer_model] = $log->issuer_id;
        }

        $logs = $this->duplicateLogByScope($scope, $log, $entity);

        $this->saveLogs($logs);
    }
}
