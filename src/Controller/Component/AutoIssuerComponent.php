<?php

namespace Elastic\ActivityLogger\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;

/**
 * AutoIssuer component
 */
class AutoIssuerComponent extends Component implements EventListenerInterface
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'userModel' => 'Users',
    ];

    /**
     * ログインユーザー
     *
     * @var \Cake\ORM\Entity
     */
    protected $issuer = null;

    /**
     *
     * @var \Cake\ORM\Table[]
     */
    protected $tables = [];

    public function implementedEvents()
    {
        return parent::implementedEvents() + [
            'Model.initialize' => 'onInitializeModel',
        ];
    }

    /**
     *
     * @param Event $event
     */
    public function startup(Event $event)
    {
        if (!$this->_registry->get('Auth')) {
            // Authコンポーネントが無効
            return null;
        }

        $controller = $event->subject();
        /* @var $controller \Cake\Controller\Controller */
        $auth = $this->_registry->get('Auth');
        /* @var $auth AuthComponent */

        // ログインユーザーを取得
        $this->issuer = $this->getIssuer();

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
     * @param Event $event
     */
    public function onInitializeModel(Event $event)
    {
        $table = $event->subject();
        /* @var $table \Cake\ORM\Table */
        if (!in_array($table->registryAlias(), array_keys($this->tables))) {
            $this->tables[$table->registryAlias()] = $table;
        }

        if (!empty($this->issuer) && $table->hasBehavior('ActivityLogger/Logger')) {
            $table->logIssuer($this->issuer);
        }
    }

    /**
     * 登録されているモデルにログインユーザーをセットする
     *
     * @param \Cake\ORM\Entity $issuer
     */
    private function setIssuerToAllModel(\Cake\ORM\Entity $issuer)
    {
        foreach ($this->tables as $alias => $table) {
            if ($table->hasBehavior('ActivityLogger/Logger')) {
                $table->logIsseur($issuer);
            }
        }
    }

    /**
     * ユーザーエンティティの取得
     *
     * @return \Cake\ORM\Entity|null
     */
    private function getIssuer()
    {
        $auth = $this->_registry->get('Auth');
        /* @var $auth AuthComponent */
        $table = $this->getUserModel();
        $userId = $auth->user($table->primaryKey());
        if ($userId) {
            return $table->get($userId);
        }
        return null;
    }

    /**
     * ユーザーテーブルの取得
     *
     * @return \Cake\ORM\Table
     */
    private function getUserModel()
    {
        return TableRegistry::get($this->config('userModel'));
    }
}
