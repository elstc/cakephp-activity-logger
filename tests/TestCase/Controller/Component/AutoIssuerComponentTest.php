<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Test\TestCase\Controller\Component;

use ArrayObject;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Controller\Component\AutoIssuerComponent;
use PHPUnit\Framework\MockObject\MockObject;
use TestApp\Model\Entity\User;
use TestApp\Model\Table\ArticlesTable;
use TestApp\Model\Table\AuthorsTable;
use TestApp\Model\Table\CommentsTable;

/**
 * Elastic\ActivityLogger\Controller\Component\AutoIssuerComponent Test Case
 */
class AutoIssuerComponentTest extends TestCase
{
    public array $fixtures = [
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
    private AutoIssuerComponent $AutoIssuer;

    /**
     * @var ComponentRegistry
     */
    private ComponentRegistry $registry;

    /**
     * @var \TestApp\Model\Table\AuthorsTable
     */
    private AuthorsTable $Authors;

    /**
     * @var \TestApp\Model\Table\ArticlesTable
     */
    private ArticlesTable $Articles;

    /**
     * @var \TestApp\Model\Table\CommentsTable
     */
    private CommentsTable $Comments;

    /**
     * @var \Cake\Http\ServerRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    private ServerRequest|MockObject $request;

    /**
     * setUp method
     *
     * @return void
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Authors = $this->fetchTable('TestApp.Authors', ['className' => AuthorsTable::class]);
        $this->Articles = $this->fetchTable('TestApp.Articles', ['className' => ArticlesTable::class]);
        $this->Comments = $this->fetchTable('TestApp.Comments', ['className' => CommentsTable::class]);

        $this->request = $this->createMock(ServerRequest::class);
        $this->registry = new ComponentRegistry(new Controller($this->request));
        $this->AutoIssuer = new AutoIssuerComponent($this->registry, [
            'userModel' => 'TestApp.Users',
            'initializedTables' => [
                'TestApp.Articles',
                'TestApp.Comments',
            ],
        ]);

        EventManager::instance()->on($this->AutoIssuer);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->AutoIssuer, $this->registry, $this->Authors, $this->Articles, $this->Comments);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization(): void
    {
        // Check default config value
        $component = new AutoIssuerComponent($this->registry);
        $this->assertSame('Users', $component->getConfig('userModel'));
        $this->assertSame([], $component->getConfig('initializedTables'));
    }

    /**
     * Test Controller.startup Event hook
     *
     * - Work with Authentication plugin
     *
     * @return void
     */
    public function testStartupWithAuthenticationPlugin(): void
    {
        // Set identity
        $this->request
            ->method('getAttribute')
            ->with('identity')
            ->willReturn(new User([
                'id' => 1,
            ]));

        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        EventManager::instance()->dispatch($event);

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
    public function testStartupWithNotAuthenticated(): void
    {
        // Set identity
        $this->request
            ->method('getAttribute')
            ->with('identity')
            ->willReturn(null);

        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        EventManager::instance()->dispatch($event);

        // If not authenticated, the issuer will not be set
        $this->assertNull($this->Articles->getLogIssuer());
        $this->assertNull($this->Comments->getLogIssuer());
        $this->assertNull($this->Authors->getLogIssuer());
    }

    /**
     * Test Authentication.afterIdentify Event hook
     *
     * @return void
     */
    public function testOnAuthenticationAfterIdentify(): void
    {
        // Dispatch Authentication.afterIdentify Event
        $event = new Event('Authentication.afterIdentify');
        $event->setData(['identity' => new ArrayObject(['id' => 2])]);
        EventManager::instance()->dispatch($event);

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
    public function testOnInitializeModel(): void
    {
        // Set identity
        $this->request
            ->method('getAttribute')
            ->with('identity')
            ->willReturn(new User([
                'id' => 1,
            ]));

        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        EventManager::instance()->dispatch($event);
        // --

        // reload Table
        $this->getTableLocator()->remove('TestApp.Authors');
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->Authors = $this->fetchTable('TestApp.Authors', [
            'className' => AuthorsTable::class,
        ]);

        // will set issuer
        $this->assertInstanceOf(User::class, $this->Authors->getLogIssuer());
        $this->assertSame(1, $this->Authors->getLogIssuer()->id);
    }

    /**
     * Test Model.initialize Event hook
     *
     * @return void
     */
    public function testOnInitializeModelAtClearTableLocator(): void
    {
        // Set identity
        $this->request
            ->method('getAttribute')
            ->with('identity')
            ->willReturn(new User([
                'id' => 1,
            ]));

        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        EventManager::instance()->dispatch($event);
        // --

        // clear TableRegistry
        $this->getTableLocator()->clear();
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->Articles = $this->fetchTable('Articles', [
            'className' => ArticlesTable::class,
        ]);

        // will not set issuer
        $this->assertNull($this->Articles->getLogIssuer());
    }
}
