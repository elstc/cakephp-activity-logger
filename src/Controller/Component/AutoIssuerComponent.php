<?php

namespace Elastic\ActivityLogger\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

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

    public function initialize(array $config)
    {
        parent::initialize($config);
        EventManager::instance()->on('Model.initialize', [$this, 'onInitializeModel']);
        EventManager::instance()->on('Auth.afterIdentify', [$this, 'onAfterIdentify']);
    }

    /**
     * on Auth.afterIdentify
     *
     * - ログインユーザーをセット
     * - ログインユーザーを登録されているモデルにセットする
     *
     * @param Event $event
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
     * @param Event $event
     */
    public function onInitializeModel(Event $event)
    {
        $table = $event->subject();
        /* @var $table \Cake\ORM\Table */
        if (!in_array($table->registryAlias(), array_keys($this->tables))) {
            $this->tables[$table->registryAlias()] = $table;
        }

        // ログインユーザーが取得できていればセットする
        if (!empty($this->issuer) && $table->behaviors()->hasMethod('logIssuer')) {
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
            if ($table->behaviors()->hasMethod('logIssuer')) {
                $table->logIssuer($issuer);
            }
        }
    }

    /**
     * ユーザーエンティティの取得
     *
     * @param array $user
     * @return \Cake\ORM\Entity|null
     */
    private function getIssuerFromUserArray($user)
    {
        $table = $this->getUserModel();
        $userId = Hash::get($user, $table->primaryKey());
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
