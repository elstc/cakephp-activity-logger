<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Model\Table;

use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;
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
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('activity_logs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
    }

    /**
     * Add data type
     *
     * @param \Cake\Database\Schema\TableSchemaInterface $table the table
     * @return \Cake\Database\Schema\TableSchemaInterface
     */
    protected function _initializeSchema(TableSchemaInterface $table): TableSchemaInterface
    {
        $schema = parent::_initializeSchema($table);
        $schema->setColumnType('data', 'json');

        return $schema;
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->allowEmptyString('id', 'create');

        $validator
            ->allowEmptyDateTime('created_at');

        $validator
            ->requirePresence('scope_model', 'create')
            ->notEmptyString('scope_model');

        $validator
            ->allowEmptyString('issuer_model');

        $validator
            ->allowEmptyString('object_model');

        $validator
            ->requirePresence('level', 'create')
            ->allowEmptyString('level');

        $validator
            ->allowEmptyString('action');

        $validator
            ->allowEmptyString('message');

        $validator
            ->allowEmptyString('data');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules;
    }

    /**
     * スコープの指定
     *
     * $table->find('scope', ['scope' => $entity])
     *
     * @param \Cake\ORM\Query $query the Query
     * @param array $options query options
     * @return \Cake\ORM\Query
     */
    public function findScope(Query $query, array $options)
    {
        if (empty($options['scope'])) {
            return $query;
        }

        $where = [];
        if ($options['scope'] instanceof Entity) {
            [$scopeModel, $scopeId] = $this->buildObjectParameter($options['scope']);
            $where[$this->aliasField('scope_model')] = $scopeModel;
            $where[$this->aliasField('scope_id')] = $scopeId;
        } elseif (is_string($options['scope'])) {
            $where[$this->aliasField('scope_model')] = $options['scope'];
        }
        $query->where($where);

        return $query;
    }

    /**
     * Find logs from system scope
     *
     * $table->find('system')
     *
     * @param \Cake\ORM\Query $query the Query
     * @param array $options query options
     * @return \Cake\ORM\Query
     */
    public function findSystem(Query $query, array $options)
    {
        $options['scope'] = '\\' . Configure::read('App.namespace');

        return $this->findScope($query, $options);
    }

    /**
     * Find logs with specific issuer
     *
     * $table->find('issuer', ['issuer' => $entity])
     *
     * @param \Cake\ORM\Query $query the Query
     * @param array $options query options
     * @return \Cake\ORM\Query
     */
    public function findIssuer(Query $query, array $options)
    {
        if (empty($options['issuer'])) {
            return $query;
        }

        $where = [];
        if ($options['issuer'] instanceof Entity) {
            [$scopeModel, $scopeId] = $this->buildObjectParameter($options['issuer']);
            $where[$this->aliasField('issuer_model')] = $scopeModel;
            $where[$this->aliasField('issuer_id')] = $scopeId;
        }
        $query->where($where);

        return $query;
    }

    /**
     * Build parameter from an entity
     *
     * @param \Cake\Datasource\EntityInterface|null $object a entity
     * @return array [object_model, object_id]
     */
    public function buildObjectParameter(?EntityInterface $object)
    {
        $objectModel = null;
        $objectId = null;
        if ($object && $object instanceof Entity) {
            $objectTable = TableRegistry::getTableLocator()->get($object->getSource());
            $objectModel = $objectTable->getRegistryAlias();
            $objectId = $this->getScopeId($objectTable, $object);
        }

        return [$objectModel, $objectId];
    }

    /**
     * Get scope's ID
     *
     * if composite primary key, it will return concatenate values
     *
     * @param \Cake\ORM\Table $table target table
     * @param \Cake\Datasource\EntityInterface $entity a entity
     * @return string|int
     */
    public function getScopeId(Table $table, EntityInterface $entity)
    {
        $primaryKey = $table->getPrimaryKey();
        if (is_string($primaryKey)) {
            return $entity->get($primaryKey);
        }
        // concatenate values, if composite primary key
        $ids = [];
        foreach ($primaryKey as $field) {
            $ids[$field] = $entity->get($field);
        }

        return implode('_', array_values($ids));
    }
}
