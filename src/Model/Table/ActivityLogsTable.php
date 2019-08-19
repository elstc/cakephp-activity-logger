<?php

namespace Elastic\ActivityLogger\Model\Table;

use Cake\Core\Configure;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * ActivityLogs Model
 */
class ActivityLogsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('activity_logs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
    }

    /**
     * Add data type
     *
     * @param TableSchema $table the table
     * @return TableSchema
     */
    protected function _initializeSchema(TableSchema $table)
    {
        $schema = parent::_initializeSchema($table);
        if (method_exists($schema, 'setColumnType')) {
            $schema->setColumnType('data', 'json_data');
        } else {
            $schema->columnType('data', 'json_data');
        }

        return $schema;
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->allowEmpty('id', 'create');

        $validator
            ->allowEmpty('created_at');

        $validator
            ->requirePresence('scope_model', 'create')
            ->notEmpty('scope_model');

        $validator
            ->allowEmpty('issuer_model');

        $validator
            ->allowEmpty('object_model');

        $validator
            ->requirePresence('level', 'create')
            ->notEmpty('level');

        $validator
            ->allowEmpty('action');

        $validator
            ->allowEmpty('message');

        $validator
            ->allowEmpty('data');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param RulesChecker $rules The rules object to be modified.
     * @return RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        return $rules;
    }

    /**
     * スコープの指定
     *
     * $table->find('scope', ['scope' => $entity])
     *
     * @param Query $query the Query
     * @param array $options query options
     * @return Query
     */
    public function findScope(Query $query, array $options)
    {
        if (empty($options['scope'])) {
            return $query;
        }

        $where = [];
        if ($options['scope'] instanceof Entity) {
            list($scopeModel, $scopeId) = $this->buildObjectParameter($options['scope']);
            $where[$this->aliasField('scope_model')] = $scopeModel;
            $where[$this->aliasField('scope_id')] = $scopeId;
        } elseif (is_string($options['scope'])) {
            $where[$this->aliasField('scope_model')] = $options['scope'];
        }
        $query->where($where);

        return $query;
    }

    /**
     * システムスコープのログの取得
     *
     * $table->find('system')
     *
     * @param Query $query the Query
     * @param array $options query options
     * @return Query
     */
    public function findSystem(Query $query, array $options)
    {
        $options['scope'] = '\\' . Configure::read('App.namespace');

        return $this->findScope($query, $options);
    }

    /**
     * 操作者の指定
     *
     * $table->find('issuer', ['issuer' => $entity])
     *
     * @param Query $query the Query
     * @param array $options query options
     * @return Query
     */
    public function findIssuer(Query $query, array $options)
    {
        if (empty($options['issuer'])) {
            return $query;
        }

        $where = [];
        if ($options['issuer'] instanceof Entity) {
            list($scopeModel, $scopeId) = $this->buildObjectParameter($options['issuer']);
            $where[$this->aliasField('issuer_model')] = $scopeModel;
            $where[$this->aliasField('issuer_id')] = $scopeId;
        }
        $query->where($where);

        return $query;
    }

    /**
     * エンティティからパラメータの取得
     *
     * @param EntityInterface $object a entity
     * @return array [object_model, object_id]
     */
    public function buildObjectParameter($object)
    {
        $objectModel = null;
        $objectId = null;
        if ($object && $object instanceof Entity) {
            $objectTable = TableRegistry::get($object->getSource());
            $objectModel = $objectTable->getRegistryAlias();
            $objectId = $this->getScopeId($objectTable, $object);
        }

        return [$objectModel, $objectId];
    }

    /**
     * プライマリキーを取得
     *
     * 複数プライマリキーの場合は連結して返す
     *
     * @param Table $table target table
     * @param EntityInterface $entity a entity
     * @return string|int
     */
    public function getScopeId(Table $table, EntityInterface $entity)
    {
        $primaryKey = $table->getPrimaryKey();
        if (is_string($primaryKey)) {
            return $entity->get($primaryKey);
        }
        // 主キーが複数の場合は値を連結する
        $ids = [];
        foreach ($primaryKey as $field) {
            $ids[$field] = $entity->get($field);
        }

        return implode('_', array_values($ids));
    }
}
