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
 */
class LoggerBehavior extends Behavior
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'scope' => [],
    ];

    public function implementedEvents()
    {
        return parent::implementedEvents() + [
            'Model.initialize' => 'afterInit',
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
            $this->config('scope', [$this->_table->registryAlias()]);
        } else {
            $this->config('scope', $scope, false);
        }
    }

    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $table = $event->subject();
        /* @var $table Table */

        $log = $this->buildLog($table, $entity, $options);
        $logs = $this->duplicateLogByScope($log, $table, $entity, $this->config('scope'));

        $this->saveLogs($logs);
    }

    public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $table = $event->subject();
        /* @var $table Table */

        $log = $this->buildLog($table, $entity, $options);
        $log->action = ActivityLog::ACTION_DELETE;
        $log->data = $this->getData($entity);

        $logs = $this->duplicateLogByScope($log, $table, $entity, $this->config('scope'));

        $this->saveLogs($logs);
    }

    /**
     * ログを作成
     *
     * @param Table $table
     * @param EntityInterface $entity
     * @param \ArrayObject $options
     * @return ActivityLog
     */
    private function buildLog(Table $table, EntityInterface $entity, ArrayObject $options)
    {
        list($issuer_model, $issuer_id) = $this->buildIssuer();
        $object_model = $table->registryAlias();
        $object_id = $entity->{$table->primaryKey()};

        $level = LogLevel::INFO;
        $action = $entity->isNew() ? ActivityLog::ACTION_CREATE : ActivityLog::ACTION_UPDATE;
        $message = '';
        $data = $this->getDirtyData($entity);

        $logTable = $this->getLogTable();
        /* @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $logTable */
        $log = $logTable->newEntity(compact('issuer_model', 'issuer_id', 'object_model', 'object_id', 'level', 'action', 'message', 'data'));
        return $log;
    }

    /**
     * ログ発行者（操作者）の取得
     *
     * @return array
     */
    private function buildIssuer()
    {
        $issuerModel = null;
        $issuerId = null;
        $issuer = $this->config('issuer');
        if ($issuer && $issuer instanceof \Cake\ORM\Entity) {
            $issuerTable = TableRegistry::get($issuer->source());
            $issuerModel = $issuerTable->registryAlias();
            $issuerId = $issuer->get($issuerTable->primaryKey());
        }
        return [$issuerModel, $issuerId];
    }

    /**
     * ログデータをスコープに応じて複製
     *
     * @param ActivityLog $log
     * @param Table $table
     * @param EntityInterface $entity
     * @param array $scope
     */
    private function duplicateLogByScope(ActivityLog $log, Table $table, EntityInterface $entity, array $scope)
    {
        $logs = [];
        foreach ($scope as $scopeModel => $scopeId) {
            if ($scopeModel === $table->registryAlias()) {
                // モデル自身に対する更新の場合は、entityのidをセットする
                $scopeId = $entity->get($table->primaryKey());
            }
            if (empty($scopeId)) {
                continue;
            }
            $logs[] = $table->patchEntity(clone $log, [
                'scope_model' => $scopeModel,
                'scope_id'    => $scopeId,
            ]);
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
            'className' => 'Elastic/ActivityLogger.ActivityLogs',
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
     * @param mixed $args
     * @return Table
     */
    public function logScope($args = null)
    {
        if (is_null($args)) {
            // getter
            return $this->config('scope');
        }
        // setter
        $this->config('scope', $args);
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
        list($issuerModel, $issuerId) = $this->buildIssuer();
        if (in_array($issuerModel, array_keys($this->config('scope')))) {
            $this->logScope($issuer);
        }
        return $this->_table;
    }
}
