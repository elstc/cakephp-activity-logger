<?php
/**
 * @codingStandardsIgnoreFile
 */
namespace Elastic\ActivityLogger\Model\Entity {

    use Authentication\IdentityInterface;
    use Cake\ORM\Entity;

    /**
     * @property integer $id
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

    /**
     * @property integer $id
     * @property string $username
     * @property string $password
     */
    class User extends Entity implements IdentityInterface
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

    /**
     * @property integer $id
     * @property string $title
     * @property string $body
     * @property string $published
     * @property integer $author_id
     */
    class Article extends Entity
    {
        protected $_accessible = ['*' => true, 'id' => false];
    }

    /**
     * @property integer $id
     * @property string $comment
     * @property string $published
     * @property integer $article_id
     * @property integer $user_id
     */
    class Comment extends Entity
    {
        protected $_accessible = ['*' => true, 'id' => false];
    }
}

namespace Elastic\ActivityLogger\Model\Table {

    use Cake\ORM\Association\BelongsTo;
    use Cake\ORM\Association\HasMany;
    use Cake\ORM\Table;
    use Elastic\ActivityLogger\Model\Behavior\LoggerBehavior;
    use Elastic\ActivityLogger\Model\Entity\ActivityLog;
    use Elastic\ActivityLogger\Model\Entity\Article;
    use Elastic\ActivityLogger\Model\Entity\Author;
    use Elastic\ActivityLogger\Model\Entity\Comment;
    use Elastic\ActivityLogger\Model\Entity\User;

    /**
     * @param ArticlesTable|HasMany $Articles
     * @mixin LoggerBehavior
     */
    class AuthorsTable extends Table
    {
        public function initialize(array $config) : void
        {
            $this->setEntityClass(Author::class);
            $this->hasMany('Articles', [
                'className' => ArticlesTable::class,
            ]);

            $this->addBehavior('Elastic/ActivityLogger.Logger');
        }
    }

    /**
     * @param CommentsTable|HasMany $Comments
     * @mixin LoggerBehavior
     */
    class UsersTable extends Table
    {
        public function initialize(array $config) : void
        {
            $this->setEntityClass(User::class);
            $this->hasMany('Comments', [
                'className' => CommentsTable::class,
            ]);

            $this->addBehavior('Elastic/ActivityLogger.Logger');
        }
    }

    /**
     * @param AuthorsTable|BelongsTo $Authors
     * @param CommentsTable|HasMany $Comments
     * @mixin LoggerBehavior
     */
    class ArticlesTable extends Table
    {
        public function initialize(array $config) : void
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
                    'Elastic/ActivityLogger.Articles',
                    'Elastic/ActivityLogger.Authors',
                ],
            ]);
        }
    }

    /**
     * @param ArticlesTable|BelongsTo $Articles
     * @param UsersTable|BelongsTo $Users
     * @mixin LoggerBehavior
     */
    class CommentsTable extends Table
    {
        public function initialize(array $config) : void
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
                    'Elastic/ActivityLogger.Authors',
                    'Elastic/ActivityLogger.Articles',
                    'Elastic/ActivityLogger.Users',
                ],
            ]);
        }
    }

    /**
     */
    class AlterActivityLogsTable extends ActivityLogsTable
    {
        public function initialize(array $config): void
        {
            $this->setTable('activity_logs');
            $this->setEntityClass(ActivityLog::class);
        }
    }
}
