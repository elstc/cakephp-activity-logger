<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $title
 * @property string $body
 * @property string $published
 * @property int $author_id
 */
class Article extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];
}
