<?php

namespace Elastic\ActivityLogger\Model\Behavior;

use \ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;
use Psr\Log\LogLevel;

/**
 * Logger behavior
 *
 * example:
 *
 * in Table (eg. CommentsTable)
 * <pre><code>
 * public function initialize(array $config)
 * {
 *      $this->addBehavior('Elastic/ActivityLogger.Logger', [
 *          'scope' => [
 *              'Elastic/ActivityLogger.Authors',
 *              'Elastic/ActivityLogger.Articles',
 *              'Elastic/ActivityLogger.Users',
 *          ],
 *      ]);
 * }
 * </code></pre>
 *
 * set Scope/Issuer
 * <pre><code>
 * $commentsTable->logScope([$artice, $author])->logIssuer($user);
 * </code></pre>
 */
class LoggerBehavior extends Behavior
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'logModel' => 'Elastic/ActivityLogger.ActivityLogs',
        'scope'    => [],
        'systemScope' => true,
        'scopeMap' => [],
    ];

    public function implementedEvents()
    {
        return parent::implementedEvents() + [
            'Model.initialize' => 'afterInit',
        ];
    }

    public function implementedMethods()
    {
        return parent::implementedMethods() + [
            'activityLog' => 'log',
        ];
    }

    /**
     * Table.initializeの後に実行
     *
     * @param Event $event
     */
    public function afterInit(Event $event)
    {
        $scope = $this->config('scope');

        if (empty($scope)) {
            $scope = [$this->_table->registryAlias()];
        }

        if ($this->config('systemScope')) {
            $namespace = $this->config('systemScope') === true
                ? Configure::read('App.namespace')
                : $this->config('systemScope');
            $scope['\\' . $namespace] = true;
        }

        $this->config('scope', $scope, false);
        $this->config('originalScope', $scope);
    }

    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $log = $this->buildLog($entity, $this->config('issuer'));
        $log->action = $entity->isNew() ? ActivityLog::ACTION_CREATE : ActivityLog::ACTION_UPDATE;
        $log->data = $this->getDirtyData($entity);
        $log->message = $this->buildMessage($log, $entity, $this->config('issuer'));

        $logs = $this->duplicateLogByScope($this->config('scope'), $log, $entity);

        $this->saveLogs($logs);
    }

    public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $log = $this->buildLog($entity, $this->config('issuer'));
        $log->action = ActivityLog::ACTION_DELETE;
        $log->data = $this->getData($entity);
        $log->message = $this->buildMessage($log, $entity, $this->config('issuer'));

        $logs = $this->duplicateLogByScope($this->config('scope'), $log, $entity);

        $this->saveLogs($logs);
    }

    /**
     * ログスコープの設定
     *
     * @param mixed $args if $args === false リセット
     * @return Table
     */
    public function logScope($args = null)
    {
        if (is_null($args)) {
            // getter
            return $this->config('scope');
        }

        if ($args === false) {
            // reset
            $this->config('scope', $this->config('originalScope'), false);
        } else {
            // setter
            if (!is_array($args)) {
                $args = [$args];
            }
            $scope = [];
            foreach ($args as $key => $val) {
                if (is_int($key) && is_string($val)) {
                    // [0 => 'Scope']
                    $scope[$val] = true;
                } else {
                    $scope[$key] = $val;
                }
            }
            $this->config('scope', $scope);
        }
        return $this->_table;
    }

    /**
     * ログ発行者の設定
     *
     * @param \Cake\ORM\Entity $issuer
     * @return Table
     */
    public function logIssuer(\Cake\ORM\Entity $issuer = null)
    {
        if (is_null($issuer)) {
            // getter
            return $this->config('issuer');
        }
        // setter
        $this->config('issuer', $issuer);

        // scopeに含む場合、併せてscopeにセット
        list($issuerModel, $issuerId) = $this->buildObjectParameter($this->config('issuer'));
        if (in_array($issuerModel, array_keys($this->config('scope')))) {
            $this->logScope($issuer);
        }
        return $this->_table;
    }

    /**
     * メッセージ生成メソッドの設定
     *
     * @param \Elastic\ActivityLogger\Model\Behavior\callable $handler
     * @return callable
     */
    public function logMessageBuilder(callable $handler = null)
    {
        if (is_null($handler)) {
            // getter
            return $this->config('messageBuilder');
        }
        // setter
        $this->config('messageBuilder', $handler);
    }

    /**
     * カスタムログの記述
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * [
     *   'object' => Entity,
     *   'issuer' => Entity,
     *   'scope' => Entity[],
     *   'action' => string,
     *   'data' => array,
     * ]
     */
    public function log($level, $message, array $context = [])
    {
        $entity = !empty($context['object']) ? $context['object'] : null;
        $issuer = !empty($context['issuer']) ? $context['issuer'] : $this->config('issuer');
        $scope = !empty($context['scope']) ? $this->__configScope($context['scope']) : $this->config('scope');

        $log = $this->buildLog($entity, $issuer);
        $log->action = isset($context['action']) ? $context['action'] : ActivityLog::ACTION_RUNTIME;
        $log->data = isset($context['data']) ? $context['data'] : $this->getData($entity);

        $log->level = $level;
        $log->message = $message;
        $log->message = $this->buildMessage($log, $entity, $issuer);

        // issuerをscopeに含む場合、併せてscopeにセット
        if (!empty($log->issuer_id) && in_array($log->issuer_model, array_keys($this->config('scope')))) {
            $scope[$log->issuer_model] = $log->issuer_id;
        }

        $logs = $this->duplicateLogByScope($scope, $log, $entity);

        $this->saveLogs($logs);
        return $logs;
    }

    /**
     * アクティビティログの取得
     *
     * $table->find('activity', ['scope' => $entity])
     *
     * @param \Cake\ORM\Query $query
     * @param array $options
     * @return \Cake\ORM\Query
     */
    public function findActivity(\Cake\ORM\Query $query, array $options)
    {
        $logTable = $this->getLogTable();
        $query = $logTable->find();

        $where = [$logTable->aliasField('scope_model') => $this->_table->registryAlias()];

        if (isset($options['scope']) && $options['scope'] instanceof \Cake\ORM\Entity) {
            list($scopeModel, $scopeId) = $this->buildObjectParameter($options['scope']);
            $where[$logTable->aliasField('scope_model')] = $scopeModel;
            $where[$logTable->aliasField('scope_id')] = $scopeId;
        }

        $query->where($where)->order([$logTable->aliasField('id') => 'desc']);

        return $query;
    }

    /**
     * ログを作成
     *
     * @param EntityInterface $entity
     * @param EntityInterface $issuer
     * @return ActivityLog
     */
    private function buildLog(EntityInterface $entity = null, EntityInterface $issuer = null)
    {
        list($issuer_model, $issuer_id) = $this->buildObjectParameter($issuer);
        list($object_model, $object_id) = $this->buildObjectParameter($entity);

        $level = LogLevel::INFO;
        $message = '';

        $logTable = $this->getLogTable();
        /* @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $logTable */
        $log = $logTable->newEntity(compact('issuer_model', 'issuer_id', 'object_model', 'object_id', 'level', 'message'));
        return $log;
    }

    /**
     * エンティティからパラメータの取得
     *
     * @param \Cake\ORM\Entity $object
     * @return array [object_model, object_id]
     * @see \Elastic\ActivityLogger\Model\Table\ActivityLogsTable::buildObjectParameter()
     */
    private function buildObjectParameter($object)
    {
        return $this->getLogTable()->buildObjectParameter($object);
    }

    /**
     * メッセージの生成
     *
     * @param ActivityLog $log
     * @param EntityInterface $entity
     * @param EntityInterface $issuer
     * @return string
     */
    private function buildMessage($log, $entity = null, $issuer = null)
    {
        if (!is_callable($this->config('messageBuilder'))) {
            return $log->message;
        }
        $context = ['object' => $entity, 'issuer' => $issuer];
        return call_user_func($this->config('messageBuilder'), $log, $context);
    }

    /**
     * ログデータをスコープに応じて複製
     *
     * @param array $scope
     * @param ActivityLog $log
     * @param EntityInterface $entity
     * @return ActivityLog[]
     */
    private function duplicateLogByScope(array $scope, ActivityLog $log, EntityInterface $entity = null)
    {
        $logs = [];

        if (!empty($entity)) {
            // フィールド値から自動マッピング
            foreach ($this->config('scopeMap') as $field => $scopeModel) {
                if (!empty($entity->get($field)) && array_key_exists($scopeModel, $scope)) {
                    $scope[$scopeModel] = $entity->get($field);
                }
            }
        }

        foreach ($scope as $scopeModel => $scopeId) {
            if (!empty($entity) && $scopeModel === $this->_table->registryAlias()) {
                // モデル自身に対する更新の場合は、entityのidをセットする
                $scopeId = $this->getLogTable()->getScopeId($this->_table, $entity);
            }
            if (empty($scopeId)) {
                continue;
            }
            $new = $this->getLogTable()->newEntity($log->toArray() + [
                'scope_model' => $scopeModel,
                'scope_id'    => $scopeId,
            ]);
            $logs[] = $new;
        }
        return $logs;
    }

    /**
     *
     * @param ActivityLog[] $logs
     */
    private function saveLogs($logs)
    {
        $logTable = $this->getLogTable();
        /* @var \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $logTable */
        foreach ($logs as $log) {
            $logTable->save($log, ['atomic' => false]);
        }
    }

    /**
     *
     * @return \Elastic\ActivityLogger\Model\Table\ActivityLogsTable
     */
    private function getLogTable()
    {
        return TableRegistry::get('ActivityLog', [
            'className' => $this->config('logModel'),
        ]);
    }

    /**
     * エンティティ変更値の取得
     *
     * hiddenに設定されたものは除く
     *
     * @param EntityInterface $entity
     * @return array
     */
    private function getDirtyData(EntityInterface $entity = null)
    {
        if (empty($entity)) {
            return null;
        }
        return $entity->extract($entity->visibleProperties(), true);
    }

    /**
     * エンティティ値の取得
     *
     * hiddenに設定されたものは除く
     *
     * @param EntityInterface $entity
     * @return array
     */
    private function getData(EntityInterface $entity = null)
    {
        if (empty($entity)) {
            return null;
        }
        return $entity->extract($entity->visibleProperties());
    }

    protected function _configWrite($key, $value, $merge = false)
    {
        if ($key === 'scope') {
            $value = $this->__configScope($value);
        }
        parent::_configWrite($key, $value, $merge);
    }

    /**
     * scope設定
     *
     * @param mixed $value
     * @return array
     */
    private function __configScope($value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $new = [];
        foreach ($value as $key => $arg) {
            if (is_string($key)) {
                $new[$key] = $arg;
            } elseif (is_string($arg)) {
                $new[$arg] = null;
            } elseif ($arg instanceof \Cake\ORM\Entity) {
                $table = TableRegistry::get($arg->source());
                $scopeId = $this->getLogTable()->getScopeId($table, $arg);
                $new[$table->registryAlias()] = $scopeId;
            }
        }

        return $new;
    }
}
