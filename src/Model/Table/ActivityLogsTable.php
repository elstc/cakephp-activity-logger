<?php

namespace Elastic\ActivityLogger\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;

/**
 * ActivityLogs Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Scopes
 * @property \Cake\ORM\Association\BelongsTo $Issuers
 * @property \Cake\ORM\Association\BelongsTo $Objects
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
}
