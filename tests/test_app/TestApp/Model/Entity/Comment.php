<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $comment
 * @property string $published
 * @property int $article_id
 * @property int $user_id
 */
class Comment extends Entity
{
    protected array $_accessible = ['*' => true, 'id' => false];
}
