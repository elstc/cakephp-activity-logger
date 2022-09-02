<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;
use TestApp\Model\Entity\Comment;

/**
 * @param ArticlesTable&\Cake\ORM\Association\BelongsTo $Articles
 * @param UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method Comment get($primaryKey, array $options = [])
 * @method Comment newEntity(array $data, array $options = [])
 * @mixin \Elastic\ActivityLogger\Model\Behavior\LoggerBehavior
 */
class CommentsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setEntityClass(Comment::class);
        $this->belongsTo('Article', [
            'className' => ArticlesTable::class,
        ]);
        $this->belongsTo('User', [
            'className' => UsersTable::class,
        ]);

        $this->addBehavior('Elastic/ActivityLogger.Logger', [
            'systemScope' => false,
            'scope' => [
                'TestApp.Authors',
                'TestApp.Articles',
                'TestApp.Users',
            ],
        ]);
    }
}
