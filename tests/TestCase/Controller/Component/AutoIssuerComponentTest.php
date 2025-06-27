<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */
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
use TestApp\Model\Entity\Author;
use TestApp\Model\Entity\User;
use TestApp\Model\Table\ArticlesTable;
use TestApp\Model\Table\AuthorsTable;
use TestApp\Model\Table\CommentsTable;

/**
 * Elastic\ActivityLogger\Controller\Component\AutoIssuerComponent Test Case
 *
 * @coversDefaultClass \Elastic\ActivityLogger\Controller\Component\AutoIssuerComponent
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
     */
    private AutoIssuerComponent $AutoIssuer;

    /**
     * @var ComponentRegistry<\Cake\Controller\Controller>
     */
    private ComponentRegistry $registry;

    private AuthorsTable $Authors;

    private ArticlesTable $Articles;

    private CommentsTable $Comments;

    private ServerRequest&MockObject $mockRequest;

    /**
     * setUp method
     *
     * @return void
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    public function setUp(): void
    {
        parent::setUp();

        // @phpstan-ignore-next-line
        $this->Authors = $this->fetchTable('TestApp.Authors', ['className' => AuthorsTable::class]);
        // @phpstan-ignore-next-line
        $this->Articles = $this->fetchTable('TestApp.Articles', ['className' => ArticlesTable::class]);
        // @phpstan-ignore-next-line
        $this->Comments = $this->fetchTable('TestApp.Comments', ['className' => CommentsTable::class]);

        $this->mockRequest = $this->createMock(ServerRequest::class);
        $this->registry = new ComponentRegistry(new Controller($this->mockRequest));
        $this->AutoIssuer = new AutoIssuerComponent($this->registry, [
            'userModel' => 'TestApp.Users',
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

        $this->assertSame([
            'userModel' => 'Users',
            'identityAttribute' => 'identity',
        ], $component->getConfig(), 'default config should be set correctly');
    }

    /**
     * Test Controller.startup Event hook
     *
     * - Work with Authentication plugin
     *
     * @return void
     * @covers ::startup
     * @covers ::getInitializedTables
     * @covers ::setIssuerToAllModel
     */
    public function testStartupWithAuthenticationPlugin(): void
    {
        // Set identity
        $this->mockRequest
            ->method('getAttribute')
            ->with('identity')
            ->willReturn(new User([
                'id' => 1,
            ]));

        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        EventManager::instance()->dispatch($event);

        // An issuer is set to all models that have been called using the TableLocator
        $this->assertInstanceOf(User::class, $this->Authors->getLogIssuer());
        $this->assertSame(1, $this->Authors->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Articles->getLogIssuer());
        $this->assertSame(1, $this->Articles->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Comments->getLogIssuer());
        $this->assertSame(1, $this->Comments->getLogIssuer()->id);
    }

    /**
     * Test Controller.startup Event hook
     *
     * @return void
     */
    public function testStartupWithNotAuthenticated(): void
    {
        // Set identity
        $this->mockRequest
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
     * Test Controller.startup Event hook
     *
     * @return void
     */
    public function testStartupWithOtherIdentity(): void
    {
        // Set identity
        $user = new Author([
            'id' => 1,
        ]);
        $user->setSource('Authors');
        $this->mockRequest
            ->method('getAttribute')
            ->with('identity')
            ->willReturn($user);

        // Dispatch Controller.startup Event
        $event = new Event('Controller.startup');
        EventManager::instance()->dispatch($event);

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
    public function testStartupWithUnknownIdentity(): void
    {
        // Set identity
        $this->mockRequest
            ->method('getAttribute')
            ->with('identity')
            ->willReturn(new User([
                'id' => 0,
            ]));

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

        // An issuer is set to all models that have been called using the TableLocator
        $this->assertInstanceOf(User::class, $this->Authors->getLogIssuer());
        $this->assertSame(2, $this->Authors->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Articles->getLogIssuer());
        $this->assertSame(2, $this->Articles->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Comments->getLogIssuer());
        $this->assertSame(2, $this->Comments->getLogIssuer()->id);
    }

    /**
     * Test Model.initialize Event hook
     *
     * @return void
     */
    public function testOnInitializeModel(): void
    {
        // Set identity
        $this->mockRequest
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
        // @phpstan-ignore-next-line
        $this->Authors = $this->fetchTable('TestApp.Authors', [
            'className' => AuthorsTable::class,
        ]);
        assert($this->Authors instanceof AuthorsTable);

        // will set issuer
        $this->assertInstanceOf(User::class, $this->Authors->getLogIssuer());
        $this->assertSame(1, $this->Authors->getLogIssuer()->id);
    }

    /**
     * Test Model.initialize Event hook when TableLocator is cleared
     *
     * @return void
     */
    public function testOnInitializeModelAtClearTableLocator(): void
    {
        // Set identity
        $this->mockRequest
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
        // @phpstan-ignore-next-line
        $this->Articles = $this->fetchTable('Articles', [
            'className' => ArticlesTable::class,
        ]);
        assert($this->Articles instanceof ArticlesTable);

        // will not set issuer (because TableLocator was cleared)
        $this->assertNull($this->Articles->getLogIssuer());
    }
}
