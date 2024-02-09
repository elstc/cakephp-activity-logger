<?php

namespace Elastic\ActivityLogger\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;
use Psr\Log\LogLevel;

/**
 * ActivityLogsFixture
 */
class ActivityLogsFixture extends TestFixture
{
    public function init(): void
    {
        parent::init();

        $this->records = [];
        $this->records[] = [
            'id' => 1,
            'created_at' => '2019-01-01 12:23:01',
            'scope_model' => 'TestApp.Authors',
            'scope_id' => '1',
            'issuer_model' => null,
            'issuer_id' => null,
            'object_model' => 'TestApp.Articles',
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
            'object_model' => 'TestApp.Articles',
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
            'object_model' => 'TestApp.Articles',
            'object_id' => '1',
            'level' => LogLevel::NOTICE,
            'action' => ActivityLog::ACTION_CREATE,
            'message' => '',
            'data' => null,
        ];
        $this->records[] = [
            'id' => 4,
            'created_at' => '2019-01-01 12:23:02',
            'scope_model' => 'TestApp.Authors',
            'scope_id' => '1',
            'issuer_model' => 'TestApp.Authors',
            'issuer_id' => '1',
            'object_model' => 'TestApp.Articles',
            'object_id' => '1',
            'level' => LogLevel::NOTICE,
            'action' => ActivityLog::ACTION_UPDATE,
            'message' => '',
            'data' => null,
        ];
        $this->records[] = [
            'id' => 5,
            'created_at' => '2019-01-01 12:23:02',
            'scope_model' => 'TestApp.Authors',
            'scope_id' => '1',
            'issuer_model' => 'TestApp.Authors',
            'issuer_id' => '2',
            'object_model' => 'TestApp.Articles',
            'object_id' => '1',
            'level' => LogLevel::NOTICE,
            'action' => ActivityLog::ACTION_DELETE,
            'message' => '',
            'data' => null,
        ];
    }
}
