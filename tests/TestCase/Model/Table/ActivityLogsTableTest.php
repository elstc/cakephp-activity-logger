<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Model\Table\ActivityLogsTable;
use TestApp\Model\Table\AuthorsTable;

/**
 * Elastic\ActivityLogger\Model\Table\ActivityLogsTable Test Case
 */
class ActivityLogsTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'plugin.Elastic/ActivityLogger.ActivityLogs',
        'plugin.Elastic/ActivityLogger.Authors',
    ];

    /**
     * @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable
     */
    private ActivityLogsTable $ActivityLogs;

    /**
     * @var \TestApp\Model\Table\AuthorsTable
     */
    private AuthorsTable $Authors;

    /**
     * setUp method
     *
     * @return       void
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'MyApp');

        $this->ActivityLogs = $this->fetchTable('Elastic/ActivityLogger.ActivityLogs');

        $this->Authors = $this->fetchTable('TestApp.Authors', ['className' => AuthorsTable::class]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ActivityLogs, $this->Authors);
        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertSame('json', $this->ActivityLogs->getSchema()->getColumnType('data'));
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    public function testFindScope(): void
    {
        $author = $this->Authors->get(1);
        $logs = $this->ActivityLogs->find('scope', ['scope' => $author])
            ->all()->toList();
        $this->assertCount(3, $logs);
        $this->assertSame('TestApp.Authors', $logs[0]->scope_model);
        $this->assertSame('1', $logs[0]->scope_id);
        $logs = $this->ActivityLogs->find('scope', ['scope' => 'Custom'])
            ->all()->toList();
        $this->assertCount(1, $logs);
        $this->assertSame('Custom', $logs[0]->scope_model);
        $this->assertSame('1', $logs[0]->scope_id);
    }

    public function testFindIssuer(): void
    {
        $author = $this->Authors->get(2);
        $logs = $this->ActivityLogs->find('issuer', ['issuer' => $author])
            ->all()->toList();
        $this->assertCount(1, $logs);
        $this->assertSame('TestApp.Authors', $logs[0]->issuer_model);
        $this->assertSame('2', $logs[0]->issuer_id);
    }

    public function testFindSystem(): void
    {
        $logs = $this->ActivityLogs->find('system')
            ->all()->toList();
        $this->assertCount(1, $logs);
        $this->assertSame('\MyApp', $logs[0]->scope_model);
        $this->assertSame('1', $logs[0]->scope_id);
    }
}
