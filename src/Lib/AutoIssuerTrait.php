<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Lib;

use ArrayAccess;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use ReflectionClass;

/**
 * AutoIssuer trait
 *
 * Provides common functionality for automatically setting issuer (logged-in user) to models
 * that use the LoggerBehavior.
 */
trait AutoIssuerTrait
{
    use LocatorAwareTrait;

    /**
     * A Logged-in User
     *
     * @var \Cake\Datasource\EntityInterface|null
     */
    protected ?EntityInterface $issuer = null;

    /**
     * @var array<\Cake\ORM\Table>
     */
    protected array $tables = [];

    /**
     * on Model.initialize
     *
     * - register the model to this component's table collection
     * - set issuer to the model
     *
     * @param \Cake\Event\Event<\Cake\ORM\Table> $event the event
     * @return void
     */
    public function onInitializeModel(Event $event): void
    {
        /** @var \Cake\ORM\Table $table */
        $table = $event->getSubject();
        if (!array_key_exists($table->getRegistryAlias(), $this->tables)) {
            $this->tables[$table->getRegistryAlias()] = $table;
        }

        // set issuer to the model if a logged-in user can get
        if (
            !empty($this->issuer) &&
            $table->hasBehavior('Logger') &&
            $this->getTableLocator()->exists($this->issuer->getSource())
        ) {
            /** @var \Elastic\ActivityLogger\Model\Behavior\LoggerBehavior $behavior */
            $behavior = $table->getBehavior('Logger');
            $behavior->setLogIssuer($this->issuer);
        }
    }

    /**
     * Get initialized models from the TableLocator
     *
     * Note: This method uses reflection to access the internal instances property
     * of the TableLocator. This approach may be fragile and could break if
     * CakePHP changes its internal implementation.
     *
     * @return array<string, \Cake\ORM\Table>
     */
    protected function getInitializedTables(): array
    {
        $locator = $this->getTableLocator();
        $reflectionClass = new ReflectionClass($locator);

        if (!$reflectionClass->hasProperty('instances')) {
            Log::debug('TableLocator does not have instances property, returning empty array');

            return [];
        }

        $property = $reflectionClass->getProperty('instances');
        $instances = $property->getValue($locator);

        if (!is_array($instances)) {
            Log::debug('TableLocator instances property is not an array, returning empty array');

            return [];
        }

        return $instances;
    }

    /**
     * Set issuer to all models
     *
     * @param \Cake\Datasource\EntityInterface $issuer An issuer
     * @return void
     */
    protected function setIssuerToAllModel(EntityInterface $issuer): void
    {
        foreach ($this->tables as $table) {
            if ($table->hasBehavior('Logger')) {
                /** @var \Elastic\ActivityLogger\Model\Behavior\LoggerBehavior $behavior */
                $behavior = $table->getBehavior('Logger');
                $behavior->setLogIssuer($issuer);
            }
        }
    }

    /**
     * Get issuer from logged in user data
     *
     * @param \ArrayAccess<string, mixed>|array<string, mixed>|null $user a User entity
     * @return \Cake\Datasource\EntityInterface|null
     */
    protected function getIssuerFromUserArray(array|ArrayAccess|null $user): ?EntityInterface
    {
        if ($user === null) {
            return null;
        }

        $table = $this->getUserModel();

        if ($user instanceof EntityInterface && $user->getSource()) {
            return is_a($user, $table->getEntityClass()) ? $user : null;
        }

        $primaryKey = $table->getPrimaryKey();
        if (is_string($primaryKey)) {
            $userId = Hash::get($user, $primaryKey);
            if ($userId) {
                return $table->find()->where([$primaryKey => $userId])->first();
            }
        }

        return null;
    }

    /**
     * Get Users table class
     *
     * @return \Cake\ORM\Table
     */
    abstract protected function getUserModel(): Table;
}
