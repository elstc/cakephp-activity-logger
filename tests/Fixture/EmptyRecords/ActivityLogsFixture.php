<?php

namespace Elastic\ActivityLogger\Test\Fixture\EmptyRecords;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ActivityLogsFixture
 *
 */
class ActivityLogsFixture extends TestFixture
{

    public $import = ['table' => 'activity_logs', 'connection' => 'default'];
    public $records = [];

}
