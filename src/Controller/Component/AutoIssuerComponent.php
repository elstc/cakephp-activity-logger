<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;

/**
 * AutoIssuer component
 *
 * Get authentication information from Authentication plugin (or AuthComponent) and set it to each Table as Issuer.
 *
 * config:
 *  'userModel': Set Identifiers 'userModel'.
 *  'identityAttribute': The request attribute used to store the identity.
 *  'initializedTables': If there is load to the Table class before the execution of `Controller.startup` event,
 *                       please describe here.
 *
 * @todo support Authentication plugin
 */
class AutoIssuerComponent extends Component
{
    use LocatorAwareTrait;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'userModel' => 'Users',
        'identityAttribute' => 'identity',
        'initializedTables' => [],
    ];

    /**
     * A Logged in User
     *
     * @var \Cake\ORM\Entity
     */
    protected $issuer = null;

    /**
     * @var \Cake\ORM\Table[]
     */
    protected $tables = [];

    /**
     * @var \Cake\ORM\Locator\LocatorInterface
     */
    protected $tableLocator;

    /**
     * AutoIssuerComponent constructor.
     *
     * @param \Cake\Controller\ComponentRegistry $registry the ComponentRegistry
     * @param array $config the config option
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);

        $this->tableLocator = $this->getTableLocator();

        $this->setInitializedTables($this->getConfig('initializedTables'));
    }

    /**
     * @return array
     */
    public function implementedEvents(): array
    {
        EventManager::instance()->on('Model.initialize', [$this, 'onInitializeModel']);

        return parent::implementedEvents() + [
                'Auth.afterIdentify' => 'onAfterIdentify',
            ];
    }

    /**
     * on Controller.startup
     *
     * @return void
     */
    public function startup()
    {
        $loadedComponents = $this->_registry->loaded();

        $auth = null;
        // Get a logged in user from AuthComponent
        if (in_array('Auth', $loadedComponents)) {
            $auth = $this->_registry->get('Auth');
            if (is_a($auth, '\Cake\Controller\Component\AuthComponent')) {
                $this->issuer = $this->getIssuerFromUserArray($auth->user());
            }
        }

        // Get a logged in user from the request identity attribute
        if (!$this->issuer) {
            $identity = $this->_registry->getController()->getRequest()
                ->getAttribute($this->getConfig('identityAttribute'));
            if ($identity) {
                $this->issuer = $this->getIssuerFromUserArray($identity->getOriginalData());
            }
        }

        if (!$this->issuer) {
            // not logged in
            return;
        }

        // register issuer to the model
        $this->setIssuerToAllModel($this->issuer);
    }

    /**
     * on Auth.afterIdentify
     *
     * - get issuer from event data
     * - register issuer to the model
     *
     * @param \Cake\Event\Event $event the Event
     * @return void
     * @noinspection PhpUnused
     */
    public function onAfterIdentify(Event $event)
    {
        [$user] = $event->getData();
        /** @var array $user */
        $this->issuer = $this->getIssuerFromUserArray($user);

        if (!$this->issuer) {
            // not logged in
            return;
        }

        // register issuer to the model
        $this->setIssuerToAllModel($this->issuer);
    }

    /**
     * on Model.initialize
     *
     * - register the model to this component's table collection
     * - set issuer to the model
     *
     * @param \Cake\Event\Event $event the event
     * @return void
     */
    public function onInitializeModel(Event $event)
    {
        $table = $event->getSubject();
        /** @var \Cake\Orm\Table $table */
        if (!array_key_exists($table->getRegistryAlias(), $this->tables)) {
            $this->tables[$table->getRegistryAlias()] = $table;
        }

        // set issuer to the model, if logged in user can get
        if (
            !empty($this->issuer) &&
            $table->behaviors()->hasMethod('setLogIssuer') &&
            $this->getTableLocator()->exists($this->issuer->getSource())
        ) {
            $table->setLogIssuer($this->issuer);
        }
    }

    /**
     * Set initialized models to this component's table collection
     *
     * @param array $tables tables
     * @return void
     */
    private function setInitializedTables(array $tables)
    {
        foreach ($tables as $tableName) {
            if ($this->tableLocator->exists($tableName)) {
                $this->tables[$tableName] = $this->tableLocator->get($tableName);
            }
        }
    }

    /**
     * Set issuer to all models
     *
     * @param \Cake\Datasource\EntityInterface $issuer A issuer
     * @return void
     */
    private function setIssuerToAllModel(EntityInterface $issuer)
    {
        foreach ($this->tables as $alias => $table) {
            if ($table->behaviors()->hasMethod('setLogIssuer')) {
                /** @noinspection PhpUndefinedMethodInspection */
                $table->setLogIssuer($issuer);
            }
        }
    }

    /**
     * Get issuer from logged in user data
     *
     * @param array|\ArrayAccess|null $user a User entity
     * @return \Cake\Datasource\EntityInterface|null
     */
    private function getIssuerFromUserArray($user)
    {
        if ($user === null) {
            return null;
        }

        $table = $this->getUserModel();
        $userId = Hash::get($user, $table->getPrimaryKey());
        if ($userId) {
            return $table->get($userId);
        }

        return null;
    }

    /**
     * Get Users table class
     *
     * @return \Cake\ORM\Table
     */
    private function getUserModel()
    {
        return $this->tableLocator->get($this->getConfig('userModel'));
    }
}
