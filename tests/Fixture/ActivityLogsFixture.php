<?php

namespace Elastic\ActivityLogger\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;
use Psr\Log\LogLevel;

/**
 * ActivityLogsFixture
 *
 */
class ActivityLogsFixture extends TestFixture
{
    public $fields = [
        'id' => ['type' => 'biginteger', 'length' => 20, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'created_at' => ['type' => 'timestamp', 'length' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'comment' => '', 'precision' => null],
        'scope_model' => ['type' => 'string', 'length' => 64, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'scope_id' => ['type' => 'string', 'length' => 64, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'issuer_model' => ['type' => 'string', 'length' => 64, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'issuer_id' => ['type' => 'string', 'length' => 64, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'object_model' => ['type' => 'string', 'length' => 64, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'object_id' => ['type' => 'string', 'length' => 64, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'level' => ['type' => 'string', 'length' => 16, 'null' => false, 'default' => null, 'comment' => 'ログレベル', 'precision' => null, 'fixed' => null],
        'action' => ['type' => 'string', 'length' => 64, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'message' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'data' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => 'json encoded data', 'precision' => null],
        '_indexes' => [
            'IX_scope' => ['type' => 'index', 'columns' => ['scope_model', 'scope_id'], 'length' => []],
            'IX_issuer' => ['type' => 'index', 'columns' => ['issuer_model', 'issuer_id'], 'length' => []],
            'IX_object' => ['type' => 'index', 'columns' => ['object_model', 'object_id'], 'length' => []],
            'IX_level' => ['type' => 'index', 'columns' => ['level'], 'length' => []],
            'IX_action' => ['type' => 'index', 'columns' => ['action'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci',
        ],
    ];

    public function insert(\Cake\Datasource\ConnectionInterface $db)
    {
        $this->records = [];
        $this->records[] = [
            'id' => 1,
            'created_at' => '2019-01-01 12:23:01',
            'scope_model' => 'Elastic/ActivityLogger.Authors',
            'scope_id' => '1',
            'issuer_model' => null,
            'issuer_id' => null,
            'object_model' => 'Elastic/ActivityLogger.Articles',
            'object_id' => '1',
            'level' => LogLevel::NOTICE,
            'action' => ActivityLog::ACTION_CREATE,
            'message' => '',
            'data' => null,
        ];
        $this->records[] = [
            'id' => 2,
            'created_at' => '2019-01-01 12:23:01',
            'scope_model' => '\MyApp',
            'scope_id' => '1',
            'issuer_model' => null,
            'issuer_id' => null,
            'object_model' => 'Elastic/ActivityLogger.Articles',
            'object_id' => '1',
            'level' => LogLevel::NOTICE,
            'action' => ActivityLog::ACTION_CREATE,
            'message' => '',
            'data' => null,
        ];
        $this->records[] = [
            'id' => 3,
            'created_at' => '2019-01-01 12:23:01',
            'scope_model' => 'Custom',
            'scope_id' => '1',
            'issuer_model' => null,
            'issuer_id' => null,
            'object_model' => 'Elastic/ActivityLogger.Articles',
            'object_id' => '1',
            'level' => LogLevel::NOTICE,
            'action' => ActivityLog::ACTION_CREATE,
            'message' => '',
            'data' => null,
        ];
        $this->records[] = [
            'id' => 4,
            'created_at' => '2019-01-01 12:23:02',
            'scope_model' => 'Elastic/ActivityLogger.Authors',
            'scope_id' => '1',
            'issuer_model' => 'Elastic/ActivityLogger.Authors',
            'issuer_id' => '1',
            'object_model' => 'Elastic/ActivityLogger.Articles',
            'object_id' => '1',
            'level' => LogLevel::NOTICE,
            'action' => ActivityLog::ACTION_UPDATE,
            'message' => '',
            'data' => null,
        ];
        $this->records[] = [
            'id' => 5,
            'created_at' => '2019-01-01 12:23:02',
            'scope_model' => 'Elastic/ActivityLogger.Authors',
            'scope_id' => '1',
            'issuer_model' => 'Elastic/ActivityLogger.Authors',
            'issuer_id' => '2',
            'object_model' => 'Elastic/ActivityLogger.Articles',
            'object_id' => '1',
            'level' => LogLevel::NOTICE,
            'action' => ActivityLog::ACTION_DELETE,
            'message' => '',
            'data' => null,
        ];
        parent::insert($db);
    }
}
