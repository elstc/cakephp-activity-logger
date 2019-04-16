<?php

namespace Elastic\ActivityLogger\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

/**
 * AutoIssuer component
 */
class AutoIssuerComponent extends Component
{
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
     * ログインユーザー
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

        if (method_exists(TableRegistry::class, 'getTableLocator')) {
            $this->tableLocator = TableRegistry::getTableLocator();
        } else {
            $this->tableLocator = TableRegistry::locator();
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
     * @return null
     */
    public function startup(Event $event)
    {
        if (!$this->_registry->get('Auth')) {
            // Authコンポーネントが無効
            return null;
        }

        $auth = $this->_registry->get('Auth');
        /* @var $auth AuthComponent */

        // ログインユーザーを取得
        $this->issuer = $this->getIssuerFromUserArray($auth->user());

        if (!$this->issuer) {
            // 未ログイン
            return null;
        }

        // 登録されているモデルにセットする
        $this->setIssuerToAllModel($this->issuer);
    }

    /**
     * on Auth.afterIdentify
     *
     * - ログインユーザーをセット
     * - ログインユーザーを登録されているモデルにセットする
     *
     * @param Event $event the Event
     * @return null
     */
    public function onAfterIdentify(Event $event)
    {
        list($user, $auth) = $event->data();
        /* @var $user array */
        /* @var $auth \Cake\Auth\BaseAuthenticate */
        $this->issuer = $this->getIssuerFromUserArray($user);

        if (!$this->issuer) {
            // 未ログイン
            return null;
        }

        // 登録されているモデルにセットする
        $this->setIssuerToAllModel($this->issuer);
    }

    /**
     * on Model.initialize
     *
     * - テーブルリストへの追加
     * - Issuerのセット
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

        // ログインユーザーが取得できていればセットする
        if (!empty($this->issuer) && $table->behaviors()->hasMethod('logIssuer')) {
            $table->logIssuer($this->issuer);
        }
    }

    /**
     * 初期化済テーブルを$tablesにセット
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
     * 登録されているモデルにログインユーザーをセットする
     *
     * @param Entity $issuer A issuer
     * @return void
     */
    private function setIssuerToAllModel(Entity $issuer)
    {
        foreach ($this->tables as $alias => $table) {
            if ($table->behaviors()->hasMethod('logIssuer')) {
                $table->logIssuer($issuer);
            }
        }
    }

    /**
     * ユーザーエンティティの取得
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
     * ユーザーテーブルの取得
     *
     * @return Table
     */
    private function getUserModel()
    {
        return $this->tableLocator->get($this->getConfig('userModel'));
    }
}
