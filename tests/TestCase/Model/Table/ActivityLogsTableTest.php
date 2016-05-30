<?php

namespace Elastic\ActivityLogger\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Model\Table\ActivityLogsTable;

/**
 * Elastic\ActivityLogger\Model\Table\ActivityLogsTable Test Case
 */
class ActivityLogsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable
     */
    public $ActivityLogs;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Elastic/ActivityLogger.ActivityLogs',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('ActivityLogs') ? [] : ['className' => 'Elastic\ActivityLogger\Model\Table\ActivityLogsTable'];
        $this->ActivityLogs = TableRegistry::get('ActivityLogs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->ActivityLogs);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
