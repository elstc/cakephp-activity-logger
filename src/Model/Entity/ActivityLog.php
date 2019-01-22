<?php

namespace Elastic\ActivityLogger\Model\Entity;

use Cake\ORM\Entity;

/**
 * ActivityLog Entity.
 *
 * @property int $id
 * @property \Cake\I18n\Time $created_at
 * @property string $scope_model
 * @property string $scope_id
 * @property-read Entity $scope
 * @property string $issuer_model
 * @property string $issuer_id
 * @property-read Entity $issuer
 * @property string $object_model
 * @property string $object_id
 * @property-read Entity $object
 * @property string $level
 * @property string $action
 * @property string $message
 * @property string $data
 */
class ActivityLog extends Entity
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_RUNTIME = 'runtime';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
