<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;
use TestApp\Model\Entity\Comment;

/**
 * @param ArticlesTable&BelongsTo $Articles
 * @param UsersTable&BelongsTo $Users
 * @method Comment get($primaryKey, array $options = [])
 * @method Comment newEntity(array $data, array $options = [])
 * @mixin LoggerBehavior
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
