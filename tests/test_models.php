<?php

namespace Elastic\ActivityLogger\Model\Entity {

    use Cake\ORM\Entity;

    /**
     * @property integer $id
     * @property string $username
     * @property string $password
     */
    class Author extends Entity
    {

        protected $_accessible = ['*' => true, 'id' => false];

        protected $_hidden = ['password'];

    }

    /**
     * @property integer $id
     * @property string $username
     * @property string $password
     */
    class User extends Entity
    {

        protected $_accessible = ['*' => true, 'id' => false];

        protected $_hidden = ['password'];

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

    use Cake\ORM\Table;

    /**
     * @param \Cake\ORM\Association\HasMany $Articles
     */
    class AuthorsTable extends Table
    {

        use \Elastic\ActivityLogger\Model\Behavior\LoggerBehaviorCompletion;

        public function initialize(array $config)
        {
            $this->entityClass('\Elastic\ActivityLogger\Model\Entity\Author');
            $this->hasMany('Articles', [
                'className' => '\Elastic\ActivityLogger\Model\Table\ArticlesTable',
            ]);

            $this->addBehavior('Elastic/ActivityLogger.Logger');
        }
    }

    /**
     * @param \Cake\ORM\Association\HasMany $Comments
     */
    class UsersTable extends Table
    {

        use \Elastic\ActivityLogger\Model\Behavior\LoggerBehaviorCompletion;

        public function initialize(array $config)
        {
            $this->entityClass('\Elastic\ActivityLogger\Model\Entity\User');
            $this->hasMany('Comments', [
                'className' => '\Elastic\ActivityLogger\Model\Table\CommentsTable',
            ]);

            $this->addBehavior('Elastic/ActivityLogger.Logger');
        }
    }

    /**
     * @param \Cake\ORM\Association\BelongsTo $Authors
     * @param \Cake\ORM\Association\HasMany $Comments
     */
    class ArticlesTable extends Table
    {

        use \Elastic\ActivityLogger\Model\Behavior\LoggerBehaviorCompletion;

        public function initialize(array $config)
        {
            $this->entityClass('\Elastic\ActivityLogger\Model\Entity\Article');
            $this->belongsTo('Author', [
                'className' => '\Elastic\ActivityLogger\Model\Table\AuthorsTable',
            ]);
            $this->hasMany('Comments', [
                'className' => '\Elastic\ActivityLogger\Model\Table\CommentsTable',
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
     * @param \Cake\ORM\Association\BelongsTo $Articles
     * @param \Cake\ORM\Association\BelongsTo $Users
     */
    class CommentsTable extends Table
    {

        use \Elastic\ActivityLogger\Model\Behavior\LoggerBehaviorCompletion;

        public function initialize(array $config)
        {
            $this->entityClass('\Elastic\ActivityLogger\Model\Entity\Comment');
            $this->belongsTo('Article', [
                'className' => '\Elastic\ActivityLogger\Model\Table\ArticlesTable',
            ]);
            $this->belongsTo('User', [
                'className' => '\Elastic\ActivityLogger\Model\Table\UsersTable',
            ]);

            $this->addBehavior('Elastic/ActivityLogger.Logger', [
                'systemScope' => false,
                'scope'       => [
                    'Elastic/ActivityLogger.Authors',
                    'Elastic/ActivityLogger.Articles',
                    'Elastic/ActivityLogger.Users',
                ],
            ]);
        }
    }

}
