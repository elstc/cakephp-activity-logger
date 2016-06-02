<?php

namespace Elastic\ActivityLogger\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Psr\Log\LogLevel;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;

/**
 * ActivityLogsFixture
 *
 */
class ActivityLogsFixture extends TestFixture
{

    public $import = ['table' => 'activity_logs', 'connection' => 'default'];

    public function insert(\Cake\Datasource\ConnectionInterface $db)
    {
        $this->records = [];
        $this->records[] = [
            'scope_model'  => 'Elastic/ActivityLogger.Authors',
            'scope_id'     => '1',
            'issuer_model' => null,
            'issuer_id'    => null,
            'object_model'  => 'Elastic/ActivityLogger.Articles',
            'object_id'     => '1',
            'level'        => LogLevel::NOTICE,
            'action'       => ActivityLog::ACTION_CREATE,
            'message'      => '',
            'data'         => null,
        ];
        $this->records[] = [
            'scope_model'  => '\MyApp',
            'scope_id'     => '1',
            'issuer_model' => null,
            'issuer_id'    => null,
            'object_model'  => 'Elastic/ActivityLogger.Articles',
            'object_id'     => '1',
            'level'        => LogLevel::NOTICE,
            'action'       => ActivityLog::ACTION_CREATE,
            'message'      => '',
            'data'         => null,
        ];
        $this->records[] = [
            'scope_model'  => 'Custom',
            'scope_id'     => '1',
            'issuer_model' => null,
            'issuer_id'    => null,
            'object_model'  => 'Elastic/ActivityLogger.Articles',
            'object_id'     => '1',
            'level'        => LogLevel::NOTICE,
            'action'       => ActivityLog::ACTION_CREATE,
            'message'      => '',
            'data'         => null,
        ];
        $this->records[] = [
            'scope_model'  => 'Elastic/ActivityLogger.Authors',
            'scope_id'     => '1',
            'issuer_model'  => 'Elastic/ActivityLogger.Authors',
            'scope_id'     => '1',
            'object_model'  => 'Elastic/ActivityLogger.Articles',
            'object_id'     => '1',
            'level'        => LogLevel::NOTICE,
            'action'       => ActivityLog::ACTION_UPDATE,
            'message'      => '',
            'data'         => null,
        ];
        $this->records[] = [
            'scope_model'  => 'Elastic/ActivityLogger.Authors',
            'scope_id'     => '1',
            'issuer_model'  => 'Elastic/ActivityLogger.Authors',
            'issuer_id'     => '2',
            'object_model'  => 'Elastic/ActivityLogger.Articles',
            'object_id'     => '1',
            'level'        => LogLevel::NOTICE,
            'action'       => ActivityLog::ACTION_DELETE,
            'message'      => '',
            'data'         => null,
        ];
        parent::insert($db);
    }
}
