<?php

namespace Elastic\ActivityLogger\Test\Fixture\EmptyRecords;

use Cake\TestSuite\Fixture\TestFixture;

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

    public $records = [];
}
