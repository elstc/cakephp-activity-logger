<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Authentication\IdentityInterface;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $username
 * @property string $password
 */
class Author extends Entity implements IdentityInterface
{
    protected $_accessible = ['*' => true, 'id' => false];

    protected $_hidden = ['password'];

    public function getIdentifier()
    {
        return $this->id;
    }

    public function getOriginalData()
    {
        return $this;
    }
}
