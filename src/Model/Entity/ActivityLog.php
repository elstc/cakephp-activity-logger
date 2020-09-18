<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Model\Entity;

use Cake\ORM\Entity;

/**
 * ActivityLog Entity.
 *
 * @property int $id
 * @property \Cake\I18n\Time $created_at
 * @property string $scope_model
 * @property string $scope_id
 * @property-read \Cake\ORM\Entity $scope
 * @property string $issuer_model
 * @property string $issuer_id
 * @property-read \Cake\ORM\Entity $issuer
 * @property string $object_model
 * @property string $object_id
 * @property-read \Cake\ORM\Entity $object
 * @property string $level
 * @property string $action
 * @property string $message
 * @property array $data
 */
class ActivityLog extends Entity
{
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_RUNTIME = 'runtime';

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
