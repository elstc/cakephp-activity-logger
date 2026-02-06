<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Test\TestCase\Model\Table;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Model\Behavior\LoggerBehavior;
use Elastic\ActivityLogger\Model\Table\LoggerTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TestApp\Model\Table\ArticlesTable;
use TestApp\Model\Table\AuthorsTable;

/**
 * LoggerTrait Test Case
 */
#[CoversClass(LoggerTrait::class)]
class LoggerTraitTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * @var \TestApp\Model\Table\ArticlesTable
     */
    protected ArticlesTable $Articles;

    /**
     * @var \TestApp\Model\Table\AuthorsTable
     */
    protected AuthorsTable $Authors;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Elastic/ActivityLogger.Authors',
        'plugin.Elastic/ActivityLogger.Articles',
        'plugin.Elastic/ActivityLogger.Comments',
        'plugin.Elastic/ActivityLogger.Users',
        'plugin.Elastic/ActivityLogger.ActivityLogs',
    ];

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Articles = $this->fetchTable('TestApp.Articles');
        $this->Authors = $this->fetchTable('TestApp.Authors');
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Articles, $this->Authors);
        parent::tearDown();
    }

    #[Test]
    public function getLogScope_returnsCurrentScope(): void
    {
        // Arrange
        // -----------------------------------------------
        // The ArticlesTable is configured with scope ['TestApp.Articles', 'TestApp.Authors']

        // Act
        // -----------------------------------------------
        // Get the log scope from the table via the trait method
        $scope = $this->Articles->getLogScope();

        // Assert
        // -----------------------------------------------
        // The scope should contain the configured scopes and the app namespace
        $this->assertArrayHasKey('TestApp.Articles', $scope);
        $this->assertArrayHasKey('TestApp.Authors', $scope);
    }

    #[Test]
    public function setLogScope_setsScope_returnsTable(): void
    {
        // Arrange
        // -----------------------------------------------
        // Get an author entity to use as scope
        $author = $this->Authors->get(1);

        // Act
        // -----------------------------------------------
        // Set the log scope and verify the method chain returns the table
        $result = $this->Articles->setLogScope($author);

        // Assert
        // -----------------------------------------------
        // The method should return the table instance for chaining
        $this->assertSame($this->Articles, $result);

        // The scope should be updated with the author
        $scope = $this->Articles->getLogScope();
        $this->assertArrayHasKey('TestApp.Authors', $scope);
        $this->assertSame('1', (string)$scope['TestApp.Authors']);
    }

    #[Test]
    public function resetLogScope_restoresOriginalScope(): void
    {
        // Arrange
        // -----------------------------------------------
        // Get original scope and then change it
        $originalScope = $this->Articles->getLogScope();
        $author = $this->Authors->get(1);
        $this->Articles->setLogScope($author);

        // Act
        // -----------------------------------------------
        // Reset the scope and verify the method chain
        $result = $this->Articles->resetLogScope();

        // Assert
        // -----------------------------------------------
        // The method should return the table instance
        $this->assertSame($this->Articles, $result);

        // The scope should be restored to the original
        $this->assertSame($originalScope, $this->Articles->getLogScope());
    }

    #[Test]
    public function getLogIssuer_withNoIssuerSet_returnsNull(): void
    {
        // Arrange
        // -----------------------------------------------
        // Fresh table without issuer set

        // Act
        // -----------------------------------------------
        // Get the log issuer
        $issuer = $this->Articles->getLogIssuer();

        // Assert
        // -----------------------------------------------
        // Should return null when no issuer is set
        $this->assertNull($issuer);
    }

    #[Test]
    public function setLogIssuer_setsIssuer_returnsTable(): void
    {
        // Arrange
        // -----------------------------------------------
        // Get an author entity to use as issuer
        $author = $this->Authors->get(1);

        // Act
        // -----------------------------------------------
        // Set the log issuer
        $result = $this->Articles->setLogIssuer($author);

        // Assert
        // -----------------------------------------------
        // The method should return the table instance for chaining
        $this->assertSame($this->Articles, $result);

        // The issuer should be set
        $issuer = $this->Articles->getLogIssuer();
        $this->assertSame($author, $issuer);
    }

    #[Test]
    public function getLogMessageBuilder_withNoBuilderSet_returnsNull(): void
    {
        // Arrange
        // -----------------------------------------------
        // Fresh table without message builder set

        // Act
        // -----------------------------------------------
        // Get the log message builder
        $builder = $this->Articles->getLogMessageBuilder();

        // Assert
        // -----------------------------------------------
        // Should return null when no builder is set
        $this->assertNull($builder);
    }

    #[Test]
    public function setLogMessageBuilder_setsBuilder_returnsTable(): void
    {
        // Arrange
        // -----------------------------------------------
        // Create a message builder callback
        $builder = fn($log) => 'custom message';

        // Act
        // -----------------------------------------------
        // Set the log message builder
        $result = $this->Articles->setLogMessageBuilder($builder);

        // Assert
        // -----------------------------------------------
        // The method should return the table instance for chaining
        $this->assertSame($this->Articles, $result);

        // The message builder should be set
        $this->assertSame($builder, $this->Articles->getLogMessageBuilder());
    }

    #[Test]
    public function setLogMessage_setsMessage_returnsTable(): void
    {
        // Arrange
        // -----------------------------------------------
        // Define a custom message
        $message = 'Test log message';

        // Act
        // -----------------------------------------------
        // Set the log message
        $result = $this->Articles->setLogMessage($message);

        // Assert
        // -----------------------------------------------
        // The method should return the table instance for chaining
        $this->assertSame($this->Articles, $result);

        // The message builder should be set
        $this->assertNotNull($this->Articles->getLogMessageBuilder());
    }

    #[Test]
    public function activityLog_recordsCustomLog(): void
    {
        // Arrange
        // -----------------------------------------------
        // Set up scope for logging
        $author = $this->Authors->get(1);
        $this->Articles->setLogScope($author);

        // Act
        // -----------------------------------------------
        // Create a custom activity log
        $logs = $this->Articles->activityLog(
            LogLevel::WARNING,
            'Test warning message',
            ['action' => 'test_action'],
        );

        // Assert
        // -----------------------------------------------
        // The method should return an array of log entries
        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);

        // Verify the log content
        $log = $logs[0];
        $this->assertSame(LogLevel::WARNING, $log->level);
        $this->assertSame('Test warning message', $log->message);
        $this->assertSame('test_action', $log->action);
    }

    #[Test]
    public function methodChaining_worksWithMultipleMethods(): void
    {
        // Arrange
        // -----------------------------------------------
        // Get entities for testing
        $author = $this->Authors->get(1);

        // Act
        // -----------------------------------------------
        // Chain multiple methods together
        $result = $this->Articles
            ->setLogScope($author)
            ->setLogIssuer($author)
            ->setLogMessage('Chained message');

        // Assert
        // -----------------------------------------------
        // The final result should be the table instance
        $this->assertSame($this->Articles, $result);

        // All settings should be applied
        $scope = $this->Articles->getLogScope();
        $this->assertArrayHasKey('TestApp.Authors', $scope);
        $this->assertSame($author, $this->Articles->getLogIssuer());
        $this->assertNotNull($this->Articles->getLogMessageBuilder());
    }

    #[Test]
    public function traitMethods_delegateToBehavior(): void
    {
        // Arrange
        // -----------------------------------------------
        // Get the behavior directly to compare

        // Act
        // -----------------------------------------------
        // Call methods through the trait
        $traitScope = $this->Articles->getLogScope();

        // Get behavior directly
        /** @var LoggerBehavior $behavior */
        $behavior = $this->Articles->getBehavior('Logger');
        $behaviorScope = $behavior->getLogScope();

        // Assert
        // -----------------------------------------------
        // The trait should return the same values as the behavior
        $this->assertSame($behaviorScope, $traitScope);
    }
}
