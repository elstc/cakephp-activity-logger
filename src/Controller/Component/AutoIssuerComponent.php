<?php

namespace Elastic\ActivityLogger\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Locator\LocatorInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

/**
 * AutoIssuer component
 *
 * Get authentication information from AuthComponent and set it to each Table as Issuer.
 *
 * config:
 *  'userModel': Set AuthComponent's 'userModel'.
 *  'initializedTables': If there is load to the Table class before the execution of `Controller.startup` event,
 *                       please describe here.
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
        'initializedTables' => [],
    ];

    /**
     * A Logged in User
     *
     * @var Entity
     */
    protected $issuer = null;

    /**
     *
     * @var Table[]
     */
    protected $tables = [];

    /**
     * @var LocatorInterface
     */
    protected $tableLocator;

    /**
     * AutoIssuerComponent constructor.
     *
     * @param ComponentRegistry $registry the ComponentRegistry
     * @param array $config the config option
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);

        if (method_exists($this, 'getTableLocator')) {
            $this->tableLocator = $this->getTableLocator();
        } else {
            $this->tableLocator = $this->tableLocator();
        }

        $this->setInitializedTables($this->getConfig('initializedTables'));
    }

    /**
     * @return array
     */
    public function implementedEvents()
    {
        EventManager::instance()->on('Model.initialize', [$this, 'onInitializeModel']);

        return parent::implementedEvents() + [
                'Auth.afterIdentify' => 'onAfterIdentify',
            ];
    }

    /**
     * on Controller.startup
     *
     * @param Event $event the Event
     * @return void
     */
    public function startup(Event $event)
    {
        $auth = $this->_registry->get('Auth');
        /* @var $auth AuthComponent */

        if (!$auth) {
            // AuthComponent is disabled
            return;
        }

        // Get a logged in user from AuthComponent
        $this->issuer = $this->getIssuerFromUserArray($auth->user());

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
     * @param Event $event the Event
     * @return void
     */
    public function onAfterIdentify(Event $event)
    {
        list($user) = $event->getData();
        /* @var $user array */
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
     * @param Event $event the event
     * @return void
     */
    public function onInitializeModel(Event $event)
    {
        $table = $event->getSubject();
        /* @var $table Table */
        if (!array_key_exists($table->getRegistryAlias(), $this->tables)) {
            $this->tables[$table->getRegistryAlias()] = $table;
        }

        // set issuer to the model, if logged in user can get
        if (
            !empty($this->issuer) &&
            $table->behaviors()->hasMethod('setLogIssuer') &&
            TableRegistry::exists($this->issuer->getSource())
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
     * @param Entity $issuer A issuer
     * @return void
     */
    private function setIssuerToAllModel(Entity $issuer)
    {
        foreach ($this->tables as $alias => $table) {
            if ($table->behaviors()->hasMethod('setLogIssuer')) {
                $table->setLogIssuer($issuer);
            }
        }
    }

    /**
     * Get issuer from logged in user data (array)
     *
     * @param array $user a User entity
     * @return Entity|null
     */
    private function getIssuerFromUserArray($user)
    {
        $table = $this->getUserModel();
        $userId = Hash::get((array)$user, $table->getPrimaryKey());
        if ($userId) {
            return $table->get($userId);
        }

        return null;
    }

    /**
     * Get Users table class
     *
     * @return Table
     */
    private function getUserModel()
    {
        return $this->tableLocator->get($this->getConfig('userModel'));
    }
}
