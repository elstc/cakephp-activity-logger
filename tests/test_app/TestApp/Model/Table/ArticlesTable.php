<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;
use TestApp\Model\Entity\Article;

/**
 * @param AuthorsTable&BelongsTo $Authors
 * @param CommentsTable&HasMany $Comments
 * @method Article get($primaryKey, array $options = [])
 * @method Article newEntity(array $data, array $options = [])
 * @mixin LoggerBehavior
 */
class ArticlesTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setEntityClass(Article::class);
        $this->belongsTo('Author', [
            'className' => AuthorsTable::class,
        ]);
        $this->hasMany('Comments', [
            'className' => CommentsTable::class,
        ]);

        $this->addBehavior('Elastic/ActivityLogger.Logger', [
            'scope' => [
                'TestApp.Articles',
                'TestApp.Authors',
            ],
        ]);
    }
}
