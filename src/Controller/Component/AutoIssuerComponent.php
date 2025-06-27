<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Controller\Component;

use ArrayAccess;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use ReflectionClass;

/**
 * AutoIssuer component
 *
 * Get authentication information from the Authentication plugin (or AuthComponent) and set it to each Table as Issuer.
 *
 * Config:
 *  'userModel': Set Identifiers 'userModel'.
 *  'identityAttribute': The request attribute used to store the identity.
 */
class AutoIssuerComponent extends Component
{
    use LocatorAwareTrait;

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'userModel' => 'Users',
        'identityAttribute' => 'identity',
    ];

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
     * @return array<string, string>
     */
    public function implementedEvents(): array
    {
        EventManager::instance()->on('Model.initialize', [$this, 'onInitializeModel']);

        return parent::implementedEvents() + [
                'Authentication.afterIdentify' => 'onAfterIdentifyAtAuthentication',
            ];
    }

    /**
     * on Controller.startup
     *
     * @return void
     */
    public function startup(): void
    {
        // Get a logged-in user from the request identity attribute
        if (!$this->issuer) {
            $identity = $this->getController()->getRequest()
                ->getAttribute($this->getConfig('identityAttribute'));
            if ($identity) {
                $this->issuer = $this->getIssuerFromUserArray($identity->getOriginalData());
            }
        }

        if (!$this->issuer) {
            // not logged in
            return;
        }

        $this->tables = $this->getInitializedTables();

        // register issuer to the model
        $this->setIssuerToAllModel($this->issuer);
    }

    /**
     * on Authentication.afterIdentify
     *
     * - get issuer from event data
     * - register issuer to the model
     *
     * @param \Cake\Event\Event<\Cake\Controller\Component> $event the Event
     * @return void
     * @noinspection PhpUnused
     */
    public function onAfterIdentifyAtAuthentication(Event $event): void
    {
        $identity = $event->getData('identity');
        $this->issuer = $this->getIssuerFromUserArray($identity);

        if (!$this->issuer) {
            // not logged in
            return;
        }

        $this->tables = $this->getInitializedTables();

        // register issuer to the model
        $this->setIssuerToAllModel($this->issuer);
    }

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
            $table->behaviors()->hasMethod('setLogIssuer') &&
            $this->getTableLocator()->exists($this->issuer->getSource())
        ) {
            // Call the method through behaviors() to ensure it exists
            $table->behaviors()->call('setLogIssuer', [$this->issuer]);
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
    private function getInitializedTables(): array
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
    private function setIssuerToAllModel(EntityInterface $issuer): void
    {
        foreach ($this->tables as $table) {
            if ($table->behaviors()->hasMethod('setLogIssuer')) {
                // Call the method through behaviors() to ensure it exists
                $table->behaviors()->call('setLogIssuer', [$issuer]);
            }
        }
    }

    /**
     * Get issuer from logged in user data
     *
     * @param \ArrayAccess<string, mixed>|array<string, mixed>|null $user a User entity
     * @return \Cake\Datasource\EntityInterface|null
     */
    private function getIssuerFromUserArray(array|ArrayAccess|null $user): ?EntityInterface
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
    private function getUserModel(): Table
    {
        return $this->fetchTable($this->getConfig('userModel'));
    }
}
