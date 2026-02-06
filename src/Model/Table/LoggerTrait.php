<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Model\Table;

use Cake\Datasource\EntityInterface;
use Elastic\ActivityLogger\Model\Behavior\LoggerBehavior;

/**
 * Logger Trait for Table classes
 *
 * This trait provides proxy methods to LoggerBehavior for CakePHP 5.3+ compatibility.
 * Since CakePHP 5.3, calling behavior methods directly on the table is deprecated.
 *
 * Usage:
 * ```php
 * use Elastic\ActivityLogger\Model\Table\LoggerTrait;
 *
 * class ArticlesTable extends Table
 * {
 *     use LoggerTrait;
 *
 *     public function initialize(array $config): void
 *     {
 *         $this->addBehavior('Elastic/ActivityLogger.Logger');
 *     }
 * }
 * ```
 */
trait LoggerTrait
{
    /**
     * @var string The LoggerBehavior name
     */
    protected static string $loggerBehaviorName = 'Logger';

    /**
     * Get the LoggerBehavior instance
     *
     * @return \Elastic\ActivityLogger\Model\Behavior\LoggerBehavior
     */
    protected function getLoggerBehavior(): LoggerBehavior
    {
        /** @var \Elastic\ActivityLogger\Model\Behavior\LoggerBehavior $behavior */
        $behavior = $this->getBehavior(static::$loggerBehaviorName);

        return $behavior;
    }

    /**
     * Get the log scope
     *
     * @return array<string>
     */
    public function getLogScope(): array
    {
        return $this->getLoggerBehavior()->getLogScope();
    }

    /**
     * Set the log scope
     *
     * @param \Cake\Datasource\EntityInterface|array<string>|array<\Cake\Datasource\EntityInterface>|string $args the log scope
     * @return static
     */
    public function setLogScope(string|array|EntityInterface $args): static
    {
        $this->getLoggerBehavior()->setLogScope($args);

        return $this;
    }

    /**
     * Reset log scope
     *
     * @return static
     */
    public function resetLogScope(): static
    {
        $this->getLoggerBehavior()->resetLogScope();

        return $this;
    }

    /**
     * Get the log issuer
     *
     * @return \Cake\Datasource\EntityInterface|null
     */
    public function getLogIssuer(): ?EntityInterface
    {
        return $this->getLoggerBehavior()->getLogIssuer();
    }

    /**
     * Set the log issuer
     *
     * @param \Cake\Datasource\EntityInterface $issuer the issuer
     * @return static
     */
    public function setLogIssuer(EntityInterface $issuer): static
    {
        $this->getLoggerBehavior()->setLogIssuer($issuer);

        return $this;
    }

    /**
     * Get the log message builder
     *
     * @return callable|null
     */
    public function getLogMessageBuilder(): ?callable
    {
        return $this->getLoggerBehavior()->getLogMessageBuilder();
    }

    /**
     * Set the log message builder
     *
     * @param callable|null $handler the message build method
     * @return static
     */
    public function setLogMessageBuilder(?callable $handler = null): static
    {
        $this->getLoggerBehavior()->setLogMessageBuilder($handler);

        return $this;
    }

    /**
     * Set a log message
     *
     * @param string $message the message
     * @param bool $persist if true, keeps the message.
     * @return static
     */
    public function setLogMessage(string $message, bool $persist = false): static
    {
        $this->getLoggerBehavior()->setLogMessage($message, $persist);

        return $this;
    }

    /**
     * Record a custom log
     *
     * @param string $level log level
     * @param string $message log message
     * @param array<string, mixed> $context context data
     * @return array<\Elastic\ActivityLogger\Model\Entity\ActivityLog>
     */
    public function activityLog(string $level, string $message, array $context = []): array
    {
        return $this->getLoggerBehavior()->activityLog($level, $message, $context);
    }

    /**
     * Disable activity log
     *
     * @return void
     */
    public function disableActivityLog(): void
    {
        $this->getLoggerBehavior()->disableActivityLog();
    }

    /**
     * Enable activity log
     *
     * @return void
     */
    public function enableActivityLog(): void
    {
        $this->getLoggerBehavior()->enableActivityLog();
    }
}
