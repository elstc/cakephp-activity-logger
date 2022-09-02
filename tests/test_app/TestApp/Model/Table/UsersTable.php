<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;
use TestApp\Model\Entity\User;

/**
 * @param CommentsTable&\Cake\ORM\Association\HasMany $Comments
 * @method User get($primaryKey, array $options = [])
 * @method User newEntity(array $data, array $options = [])
 * @mixin \Elastic\ActivityLogger\Model\Behavior\LoggerBehavior
 */
class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setEntityClass(User::class);
        $this->hasMany('Comments', [
            'className' => CommentsTable::class,
        ]);

        $this->addBehavior('Elastic/ActivityLogger.Logger');
    }
}
