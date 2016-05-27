<?php

namespace Elastic\ActivityLogger\Test\TestCase\Model\Behavior;

use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Model\Behavior\LoggerBehavior;

/**
 * Elastic\ActivityLogger\Model\Behavior\LoggerBehavior Test Case
 */
class LoggerBehaviorTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Elastic\ActivityLogger\Model\Behavior\LoggerBehavior
     */
    public $Logger;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Logger = new LoggerBehavior(new \Cake\ORM\Table);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Logger);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
