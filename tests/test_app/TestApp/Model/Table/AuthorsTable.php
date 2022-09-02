<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;
use TestApp\Model\Entity\Author;

/**
 * @param ArticlesTable&\Cake\ORM\Association\HasMany $Articles
 * @method Author get($primaryKey, array $options = [])
 * @method Author newEntity(array $data, array $options = [])
 * @mixin \Elastic\ActivityLogger\Model\Behavior\LoggerBehavior
 */
class AuthorsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setEntityClass(Author::class);
        $this->hasMany('Articles', [
            'className' => ArticlesTable::class,
        ]);

        $this->addBehavior('Elastic/ActivityLogger.Logger');
    }
}
