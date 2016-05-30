<?php

namespace Elastic\ActivityLogger\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
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
    protected $_defaultConfig = [];

    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $table = $event->subject();
        /* @var $table Table */

        $log = $this->buildLog($table, $entity, $options);

        $logTable = $this->getLogTable();
        /* @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $logTable */
        $logTable->save($log);
    }

    public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $table = $event->subject();
        /* @var $table Table */

        $log = $this->buildLog($table, $entity, $options);
        $log->action = ActivityLog::ACTION_DELETE;
        $log->data = $this->getData($entity);

        $logTable = $this->getLogTable();
        /* @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $logTable */
        $logTable->save($log);
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
        $scope_model = $table->alias();
        $scope_id = $entity->{$table->primaryKey()};
        $issuer_model = null;
        $issuer_id = null;
        $object_model = $table->alias();
        $object_id = $entity->{$table->primaryKey()};

        $level = LogLevel::INFO;
        $action = $entity->isNew() ? ActivityLog::ACTION_CREATE : ActivityLog::ACTION_UPDATE;
        $message = '';
        $data = $this->getDirtyData($entity);

        $logTable = $this->getLogTable();
        /* @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $logTable */
        $log = $logTable->newEntity(compact('scope_model', 'scope_id', 'issuer_model', 'issuer_id', 'object_model', 'object_id', 'level', 'action', 'message', 'data'));
        return $log;
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
}
