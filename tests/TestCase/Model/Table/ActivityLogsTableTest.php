<?php

namespace Elastic\ActivityLogger\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Model\Table\ActivityLogsTable;
use Elastic\ActivityLogger\Model\Table\AuthorsTable;

/**
 * Elastic\ActivityLogger\Model\Table\ActivityLogsTable Test Case
 *
 * @property ActivityLogsTable $ActivityLogs
 * @property AuthorsTable $Authors
 */
class ActivityLogsTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Elastic/ActivityLogger.ActivityLogs',
        'plugin.Elastic/ActivityLogger.Authors',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'MyApp');
        $this->ActivityLogs = TableRegistry::get('Elastic/ActivityLogger.ActivityLogs');
        $this->Authors = TableRegistry::get('Elastic/ActivityLogger.Authors');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->ActivityLogs);
        unset($this->Authors);
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

    public function testFindScope()
    {
        //
        $author = $this->Authors->get(1);
        $logs = $this->ActivityLogs->find('scope', ['scope' => $author])
            ->all()->toArray();
        $this->assertCount(3, $logs);
        $this->assertSame('Elastic/ActivityLogger.Authors', $logs[0]->scope_model);
        $this->assertSame('1', $logs[0]->scope_id);
        //
        $logs = $this->ActivityLogs->find('scope', ['scope' => 'Custom'])
            ->all()->toArray();
        $this->assertCount(1, $logs);
        $this->assertSame('Custom', $logs[0]->scope_model);
        $this->assertSame('1', $logs[0]->scope_id);
    }

    public function testFindIssuer()
    {
        //
        $author = $this->Authors->get(2);
        $logs = $this->ActivityLogs->find('issuer', ['issuer' => $author])
            ->all()->toArray();
        $this->assertCount(1, $logs);
        $this->assertSame('Elastic/ActivityLogger.Authors', $logs[0]->issuer_model);
        $this->assertSame('2', $logs[0]->issuer_id);
    }

    public function testFindSystem()
    {
        $this->markTestIncomplete();
        $logs = $this->ActivityLogs->find('system')
            ->all()->toArray();
        $this->assertCount(1, $logs);
        $this->assertSame('\MyApp', $logs[0]->scope_model);
        $this->assertSame('1', $logs[0]->scope_id);
    }
}
