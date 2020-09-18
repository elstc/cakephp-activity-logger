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
use RuntimeException;

/**
 * AutoIssuer component
 *
 * Get authentication information from AuthComponent and set it to each Table as Issuer.
 *
 * config:
 *  'userModel': Set AuthComponent's 'userModel'.
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
        try {
            # ToDo: this will possibly break the whole functionality when not using AuthComponent
            $auth = $this->_registry->get('Auth');
        /** @var \Cake\Controller\Component\AuthComponent $auth */
        } catch (RuntimeException $e) {
            $auth = null;
        }
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
     * @param \Cake\Event\Event $event the Event
     * @return void
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
        if (!empty($this->issuer) && $table->behaviors()->hasMethod('setLogIssuer')) {
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
                $table->setLogIssuer($issuer);
            }
        }
    }

    /**
     * Get issuer from logged in user data (array)
     *
     * @param array|null $user a User entity
     * @return \Cake\Datasource\EntityInterface|null
     */
    private function getIssuerFromUserArray(?array $user)
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
     * @return \Cake\ORM\Table
     */
    private function getUserModel()
    {
        return $this->tableLocator->get($this->getConfig('userModel'));
    }
}
