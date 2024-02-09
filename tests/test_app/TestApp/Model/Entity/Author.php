<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use ArrayAccess;
use Authentication\IdentityInterface;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $username
 * @property string $password
 */
class Author extends Entity implements IdentityInterface
{
    protected array $_accessible = ['*' => true, 'id' => false];

    protected array $_hidden = ['password'];

    public function getIdentifier(): array|string|int|null
    {
        return $this->id;
    }

    public function getOriginalData(): ArrayAccess|array
    {
        return $this;
    }
}
