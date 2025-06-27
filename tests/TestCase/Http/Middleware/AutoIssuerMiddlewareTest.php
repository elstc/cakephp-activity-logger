<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
declare(strict_types=1);

namespace Elastic\ActivityLogger\Test\TestCase\Http\Middleware;

use Authentication\IdentityInterface;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Http\Middleware\AutoIssuerMiddleware;
use Laminas\Diactoros\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Server\RequestHandlerInterface;
use TestApp\Model\Entity\Author;
use TestApp\Model\Entity\User;
use TestApp\Model\Table\ArticlesTable;
use TestApp\Model\Table\AuthorsTable;
use TestApp\Model\Table\CommentsTable;

/**
 * Elastic\ActivityLogger\Http\Middleware\AutoIssuerMiddleware Test Case
 *
 * @coversDefaultClass \Elastic\ActivityLogger\Http\Middleware\AutoIssuerMiddleware
 */
class AutoIssuerMiddlewareTest extends TestCase
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
     * @var AutoIssuerMiddleware
     */
    private AutoIssuerMiddleware $middleware;

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
     * @var \Psr\Http\Server\RequestHandlerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private RequestHandlerInterface&MockObject $mockHandler;

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

        $this->middleware = new AutoIssuerMiddleware([
            'userModel' => 'TestApp.Users',
        ]);

        $this->mockHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockHandler->method('handle')->willReturn(new Response());
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->middleware, $this->Authors, $this->Articles, $this->Comments, $this->mockHandler);

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
        $middleware = new AutoIssuerMiddleware();

        $this->assertSame([
            'userModel' => 'Users',
            'identityAttribute' => 'identity',
        ], $middleware->getConfig(), 'default config should be set correctly');
    }

    /**
     * Test process method with an authenticated user
     *
     * @return void
     * @covers ::process
     * @covers ::getInitializedTables
     * @covers ::setIssuerToAllModel
     */
    public function testProcessWithAuthenticatedUser(): void
    {
        // Create a request with identity
        $user = new User([
            'id' => 1,
        ]);
        $user->setSource('TestApp.Users');

        $request = new ServerRequest();
        $request = $request->withAttribute('identity', $user);

        // Process the request
        $this->middleware->process($request, $this->mockHandler);

        // An issuer is set to all models that have been called using the TableLocator
        $this->assertInstanceOf(User::class, $this->Authors->getLogIssuer());
        $this->assertSame(1, $this->Authors->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Articles->getLogIssuer());
        $this->assertSame(1, $this->Articles->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Comments->getLogIssuer());
        $this->assertSame(1, $this->Comments->getLogIssuer()->id);
    }

    /**
     * Test process method without identity
     *
     * @return void
     */
    public function testProcessWithUnauthenticatedUser(): void
    {
        // Create a request without authenticated
        $request = new ServerRequest();

        // Process the request
        $this->middleware->process($request, $this->mockHandler);

        // If not authenticated, the issuer will not be set
        $this->assertNull($this->Articles->getLogIssuer());
        $this->assertNull($this->Comments->getLogIssuer());
        $this->assertNull($this->Authors->getLogIssuer());
    }

    /**
     * Test process method with array identity data
     *
     * @return void
     */
    public function testProcessWithArrayIdentityData(): void
    {
        // Create a mock identity with the getOriginalData method
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn(['id' => 2]);

        $request = new ServerRequest();
        $request = $request->withAttribute('identity', $identity);

        // Process the request
        $this->middleware->process($request, $this->mockHandler);

        // An issuer is set to all models that have been called using the TableLocator
        $this->assertInstanceOf(User::class, $this->Authors->getLogIssuer());
        $this->assertSame(2, $this->Authors->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Articles->getLogIssuer());
        $this->assertSame(2, $this->Articles->getLogIssuer()->id);
        $this->assertInstanceOf(User::class, $this->Comments->getLogIssuer());
        $this->assertSame(2, $this->Comments->getLogIssuer()->id);
    }

    /**
     * Test process method with another entity type as identity
     *
     * @return void
     */
    public function testProcessWithOtherIdentity(): void
    {
        // Create an identity that's not a User entity
        $author = new Author([
            'id' => 1,
        ]);
        $author->setSource('Authors');

        $request = new ServerRequest();
        $request = $request->withAttribute('identity', $author);

        // Process the request
        $this->middleware->process($request, $this->mockHandler);

        // If not a User entity, the issuer will not be set
        $this->assertNull($this->Articles->getLogIssuer());
        $this->assertNull($this->Comments->getLogIssuer());
        $this->assertNull($this->Authors->getLogIssuer());
    }

    /**
     * Test process method with unknown user ID
     *
     * @return void
     */
    public function testProcessWithUnknownUser(): void
    {
        // Create a mock identity with non-existent user ID
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn(['id' => 999]);

        $request = new ServerRequest();
        $request = $request->withAttribute('identity', $identity);

        // Process the request
        $this->middleware->process($request, $this->mockHandler);

        // If a user doesn't exist, the issuer will not be set
        $this->assertNull($this->Articles->getLogIssuer());
        $this->assertNull($this->Comments->getLogIssuer());
        $this->assertNull($this->Authors->getLogIssuer());
    }

    /**
     * Test Model.initialize Event hook
     *
     * @return void
     */
    public function testOnInitializeModel(): void
    {
        // Create a request with identity
        $user = new User([
            'id' => 1,
        ]);
        $user->setSource('TestApp.Users');

        $request = new ServerRequest();
        $request = $request->withAttribute('identity', $user);

        // Process the request
        $this->middleware->process($request, $this->mockHandler);

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
        // Create a request with identity
        $user = new User([
            'id' => 1,
        ]);
        $user->setSource('TestApp.Users');

        $request = new ServerRequest();
        $request = $request->withAttribute('identity', $user);

        // Process the request
        $this->middleware->process($request, $this->mockHandler);

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
