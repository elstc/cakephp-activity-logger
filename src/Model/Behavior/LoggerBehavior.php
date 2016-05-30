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
        $logTable->save($log);
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
     * 変更値の取得
     *
     * @param EntityInterface $entity
     * @return array
     */
    private function getDirtyData(EntityInterface $entity)
    {
        return $entity->extract($entity->visibleProperties(), true);
    }
}
