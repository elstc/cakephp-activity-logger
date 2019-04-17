<?php

namespace Elastic\ActivityLogger\Test\TestCase\Controller\Component;

use Cake\Auth\BasicAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Controller\Component\AutoIssuerComponent;
use Elastic\ActivityLogger\Model\Entity\User;
use Elastic\ActivityLogger\Model\Table\ArticlesTable;
use Elastic\ActivityLogger\Model\Table\AuthorsTable;
use Elastic\ActivityLogger\Model\Table\CommentsTable;

/**
 * Elastic\ActivityLogger\Controller\Component\AutoIssuerComponent Test Case
 */
class AutoIssuerComponentTest extends TestCase
{
    public $fixtures = [
        'plugin.Elastic/ActivityLogger.Authors',
        'plugin.Elastic/ActivityLogger.Articles',
        'plugin.Elastic/ActivityLogger.Comments',
        'plugin.Elastic/ActivityLogger.Users',
    ];

    /**
     * Test subject
     *
     * @var AutoIssuerComponent
     */
    private $AutoIssuer;

    /**
     * @var ComponentRegistry
     */
    private $registry;

    /**
     * @var AuthorsTable
     */
    private $Authors;

    /**
     * @var ArticlesTable
     */
    private $Articles;

    /**
     * @var CommentsTable
     */
    private $Comments;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Authors = TableRegistry::get('Elastic/ActivityLogger.Authors');
        $this->Articles = TableRegistry::get('Elastic/ActivityLogger.Articles');
        $this->Comments = TableRegistry::get('Elastic/ActivityLogger.Comments');

        $this->registry = new ComponentRegistry();
        $this->AutoIssuer = new AutoIssuerComponent($this->registry, [
            'userModel' => 'Elastic/ActivityLogger.Users',
            'initializedTables' => [
                'Elastic/ActivityLogger.Articles',
                'Elastic/ActivityLogger.Comments',
            ],
        ]);

        EventManager::instance()->on($this->AutoIssuer);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AutoIssuer, $this->registry, $this->Authors, $this->Articles, $this->Comments);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        // Check default config value
        $component = new AutoIssuerComponent($this->registry);
        $this->assertSame('Users', $component->getConfig('userModel'));
        $this->assertSame([], $component->getConfig('initializedTables'));
    }

    /**
     * Test Controller.startup Event hook
     *
     * @return void
     */
    public function testStartup()
    {
        // Create AuthComponent mock
        $auth = $this->getMockBuilder(AuthComponent::class)
            ->disableOriginalConstructor()
            ->setMethods(['user'])
            ->getMock();
        $auth->method('user')
            ->willReturn([
                'id' => 1,
            ]);
        $this->registry->set('Auth', $auth);

        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        $result = EventManager::instance()->dispatch($event);

        // The model defined in `initializedTables` will set an issuer
        $this->assertInstanceOf(User::class, $this->Articles->getLogIssuer());
        $this->assertSame(1, $this->Articles->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Comments->getLogIssuer());
        $this->assertSame(1, $this->Comments->getLogIssuer()->id);

        // The model undefined in `initializedTables` not set the issuer
        $this->assertNull($this->Authors->getLogIssuer());
    }

    /**
     * Test Controller.startup Event hook
     *
     * @return void
     */
    public function testStartupWithNotAuthenticated()
    {
        // Create AuthComponent mock
        $auth = $this->getMockBuilder(AuthComponent::class)
            ->disableOriginalConstructor()
            ->setMethods(['user'])
            ->getMock();
        $auth->method('user')
            ->willReturn(null);
        $this->registry->set('Auth', $auth);

        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        $result = EventManager::instance()->dispatch($event);

        // If not authenticated, the issuer will not be set
        $this->assertNull($this->Articles->getLogIssuer());
        $this->assertNull($this->Comments->getLogIssuer());
        $this->assertNull($this->Authors->getLogIssuer());
    }

    /**
     * Test Controller.startup Event hook
     *
     * @return void
     */
    public function testStartupWithNonAuthComponent()
    {
        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        $result = EventManager::instance()->dispatch($event);

        // If not authenticated, the issuer will not be set
        $this->assertNull($this->Articles->getLogIssuer());
        $this->assertNull($this->Comments->getLogIssuer());
        $this->assertNull($this->Authors->getLogIssuer());
    }

    /**
     * Test AuthComponent Auth.afterIdentify Event hook
     *
     * @return void
     */
    public function testOnAfterIdentify()
    {
        // Dispatch Auth.afterIdentify Event
        $event = new Event('Auth.afterIdentify');
        $event->setData([['id' => 2], new BasicAuthenticate($this->registry)]);
        $result = EventManager::instance()->dispatch($event);

        // The model defined in `initializedTables` will set an issuer
        $this->assertInstanceOf(User::class, $this->Articles->getLogIssuer());
        $this->assertSame(2, $this->Articles->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Comments->getLogIssuer());
        $this->assertSame(2, $this->Comments->getLogIssuer()->id);

        // The model undefined in `initializedTables` not set the issuer
        $this->assertNull($this->Authors->getLogIssuer());
    }

    /**
     * Test Model.initialize Event hook
     *
     * @return void
     */
    public function testOnInitializeModel()
    {
        // -- Set issuer to AutoIssuerComponent
        // Create AuthComponent mock
        $auth = $this->getMockBuilder(AuthComponent::class)
            ->disableOriginalConstructor()
            ->setMethods(['user'])
            ->getMock();
        $auth->method('user')
            ->willReturn([
                'id' => 1,
            ]);
        $this->registry->set('Auth', $auth);

        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        $result = EventManager::instance()->dispatch($event);
        // --

        // reload Table
        TableRegistry::remove('Elastic/ActivityLogger.Authors');
        $this->Authors = TableRegistry::get('Elastic/ActivityLogger.Authors');

        // will set issuer
        $this->assertInstanceOf(User::class, $this->Authors->getLogIssuer());
        $this->assertSame(1, $this->Authors->getLogIssuer()->id);
    }
}
