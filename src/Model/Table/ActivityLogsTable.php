<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Model\Table;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ActivityLogs Model
 *
 * @method \Elastic\ActivityLogger\Model\Entity\ActivityLog get($primaryKey, array $options = [])
 */
class ActivityLogsTable extends Table implements ActivityLogsTableInterface
{
    use LocatorAwareTrait;

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

        $this->getSchema()->setColumnType('data', 'json');
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
     * find by scope
     *
     * $table->find('scope', scope: $entity)
     *
     * @param \Cake\ORM\Query\SelectQuery<\Elastic\ActivityLogger\Model\Entity\ActivityLog> $query the Query
     * @param \Cake\Datasource\EntityInterface|string|null $scope the scope entity
     * @return \Cake\ORM\Query\SelectQuery<\Elastic\ActivityLogger\Model\Entity\ActivityLog>
     */
    public function findScope(SelectQuery $query, EntityInterface|string|null $scope = null): SelectQuery
    {
        if (empty($scope)) {
            return $query;
        }

        $where = [];
        if ($scope instanceof EntityInterface) {
            [$scopeModel, $scopeId] = $this->buildObjectParameter($scope);
            $where[$this->aliasField('scope_model')] = $scopeModel;
            $where[$this->aliasField('scope_id')] = $scopeId;
        } elseif (is_string($scope)) {
            $where[$this->aliasField('scope_model')] = $scope;
        }

        $query->where($where);

        return $query;
    }

    /**
     * Find logs from the system scope
     *
     * $table->find('system')
     *
     * @param \Cake\ORM\Query\SelectQuery<\Elastic\ActivityLogger\Model\Entity\ActivityLog> $query the Query
     * @return \Cake\ORM\Query\SelectQuery<\Elastic\ActivityLogger\Model\Entity\ActivityLog>
     * @noinspection PhpUnused
     */
    public function findSystem(SelectQuery $query): SelectQuery
    {
        $scope = '\\' . Configure::read('App.namespace');

        return $this->findScope($query, scope: $scope);
    }

    /**
     * Find logs with a specific issuer
     *
     * $table->find('issuer', issuer: $entity)
     *
     * @param \Cake\ORM\Query\SelectQuery<\Elastic\ActivityLogger\Model\Entity\ActivityLog> $query the Query
     * @param \Cake\Datasource\EntityInterface|null $issuer the issuer entity
     * @return \Cake\ORM\Query\SelectQuery<\Elastic\ActivityLogger\Model\Entity\ActivityLog>
     * @noinspection PhpUnused
     */
    public function findIssuer(SelectQuery $query, ?EntityInterface $issuer = null): SelectQuery
    {
        if (empty($issuer)) {
            return $query;
        }

        $where = [];
        if ($issuer instanceof EntityInterface) {
            [$scopeModel, $scopeId] = $this->buildObjectParameter($issuer);

            $where[$this->aliasField('issuer_model')] = $scopeModel;
            $where[$this->aliasField('issuer_id')] = $scopeId;
        }

        $query->where($where);

        return $query;
    }

    /**
     * Build parameter from an entity
     *
     * @param \Cake\Datasource\EntityInterface|null $object an entity
     * @return array [object_model, object_id]
     */
    public function buildObjectParameter(?EntityInterface $object): array
    {
        $objectModel = null;
        $objectId = null;
        if ($object instanceof Entity) {
            $objectTable = $this->fetchTable($object->getSource());
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
     * @param \Cake\Datasource\EntityInterface $entity an entity
     * @return string|int
     */
    public function getScopeId(Table $table, EntityInterface $entity): string|int
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
