<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;
use Elastic\ActivityLogger\Model\Table\LoggerTrait;
use TestApp\Model\Entity\User;

/**
 * @param CommentsTable&\Cake\ORM\Association\HasMany $Comments
 * @method User get($primaryKey, array $options = [])
 * @method User newEntity(array $data, array $options = [])
 */
class UsersTable extends Table
{
    use LoggerTrait;

    public function initialize(array $config): void
    {
        $this->setEntityClass(User::class);
        $this->hasMany('Comments', [
            'className' => CommentsTable::class,
        ]);

        $this->addBehavior('Elastic/ActivityLogger.Logger');
    }
}
