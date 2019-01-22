<?php

namespace Elastic\ActivityLogger\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Controller\Component\AutoIssuerComponent;

/**
 * Elastic\ActivityLogger\Controller\Component\AutoIssuerComponent Test Case
 */
class AutoIssuerComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var AutoIssuerComponent
     */
    private $AutoIssuer;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->AutoIssuer = new AutoIssuerComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AutoIssuer);

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
