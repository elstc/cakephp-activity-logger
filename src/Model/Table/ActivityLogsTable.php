<?php

namespace Elastic\ActivityLogger\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Database\Schema\Table as Schema;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;

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

        $this->table('activity_logs');
        $this->displayField('id');
        $this->primaryKey('id');
    }

    protected function _initializeSchema(Schema $table)
    {
        $schema = parent::_initializeSchema($table);
        $schema->columnType('data', 'json_data');
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
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
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
     * @param \Cake\ORM\Query $query
     * @param array $options
     * @return \Cake\ORM\Query
     */
    public function findScope(\Cake\ORM\Query $query, array $options)
    {
        if (empty($options['scope'])) {
            return $query;
        }

        $where = [];
        if ($options['scope'] instanceof \Cake\ORM\Entity) {
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
     * @param \Cake\ORM\Query $query
     * @param array $options
     * @return \Cake\ORM\Query
     */
    public function findSystem(\Cake\ORM\Query $query, array $options)
    {
        $options['scope'] = '\\' . Configure::read('App.namespace');
        return $this->findScope($query, $options);
    }

    /**
     * 操作者の指定
     *
     * $table->find('issuer', ['issuer' => $entity])
     *
     * @param \Cake\ORM\Query $query
     * @param array $options
     * @return \Cake\ORM\Query
     */
    public function findIssuer(\Cake\ORM\Query $query, array $options)
    {
        if (empty($options['issuer'])) {
            return $query;
        }

        $where = [];
        if ($options['issuer'] instanceof \Cake\ORM\Entity) {
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
     * @param \Cake\ORM\Entity $object
     * @return array [object_model, object_id]
     */
    public function buildObjectParameter($object)
    {
        $objectModel = null;
        $objectId = null;
        if ($object && $object instanceof \Cake\ORM\Entity) {
            $objectTable = TableRegistry::get($object->source());
            $objectModel = $objectTable->registryAlias();
            $objectId = $object->get($objectTable->primaryKey());
        }
        return [$objectModel, $objectId];
    }
}
