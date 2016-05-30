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

        public function initialize(array $config)
        {
            $this->entityClass('\Elastic\ActivityLogger\Model\Entity\Comment');
            $this->belongsTo('Article', [
                'className' => '\Elastic\ActivityLogger\Model\Table\ArticlesTable',
            ]);
            $this->belongsTo('User', [
                'className' => '\Elastic\ActivityLogger\Model\Table\UsersTable',
            ]);

            $this->addBehavior('Elastic/ActivityLogger.Logger');
        }
    }

}

namespace Elastic\ActivityLogger\Test\TestCase\Model\Behavior {

    use Cake\ORM\Entity;
    use Cake\ORM\Table;
    use Cake\ORM\TableRegistry;
    use Cake\TestSuite\TestCase;
    use Elastic\ActivityLogger\Model\Behavior\LoggerBehavior;
    use Elastic\ActivityLogger\Model\Entity\ActivityLog;
    use Psr\Log\LogLevel;

    /**
     * Elastic\ActivityLogger\Model\Behavior\LoggerBehavior Test Case
     *
     * @property \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $ActivityLogs
     * @property \Elastic\ActivityLogger\Model\Table\AuthorsTable $Authors
     * @property \Elastic\ActivityLogger\Model\Table\ArticlesTable $Articles
     * @property \Elastic\ActivityLogger\Model\Table\CommentsTable $Comments
     * @property \Elastic\ActivityLogger\Model\Behavior\LoggerBehavior $Logger
     */
    class LoggerBehaviorTest extends TestCase
    {

        /**
         * Fixtures
         *
         * @var array
         */
        public $fixtures = [
            'plugin.Elastic/ActivityLogger.ActivityLogs',
            'plugin.Elastic/ActivityLogger.Authors',
            'plugin.Elastic/ActivityLogger.Articles',
            'plugin.Elastic/ActivityLogger.Comments',
            'plugin.Elastic/ActivityLogger.Users',
        ];

        /**
         * setUp method
         *
         * @return void
         */
        public function setUp()
        {
            parent::setUp();
            $this->Logger = new LoggerBehavior(new \Cake\ORM\Table);
            $this->Authors = TableRegistry::get('Authors', [
                'className' => '\Elastic\ActivityLogger\Model\Table\AuthorsTable',
            ]);
            $this->Articles = TableRegistry::get('Articles', [
                'className' => '\Elastic\ActivityLogger\Model\Table\ArticlesTable',
            ]);
            $this->Comments = TableRegistry::get('Comments', [
                'className' => '\Elastic\ActivityLogger\Model\Table\CommentsTable',
            ]);
            $this->ActivityLogs = TableRegistry::get('Elastic/ActivityLogger.ActivityLogs');
        }

        /**
         * tearDown method
         *
         * @return void
         */
        public function tearDown()
        {
            unset($this->Logger);
            unset($this->Authors);
            unset($this->Articles);
            unset($this->Comments);

            parent::tearDown();
        }

        /**
         * Test initial setup
         *
         * @return void
         */
        public function testInitialization()
        {
            $this->markTestIncomplete('Not implemented yet.');
        }

        public function testSave()
        {
            $author = $this->Authors->newEntity([
                'username' => 'foo',
                'password' => 'bar',
            ]);
            $this->Authors->save($author);
            // アクティビティログが保存されている
            $q = $this->ActivityLogs->find();
            $this->assertCount(1, $q->all());

            $log = $q->first();
            /* @var $log ActivityLog */
            $this->assertSame(LogLevel::INFO, $log->level, 'デフォルトのログレベルはinfo');
            $this->assertSame(ActivityLog::ACTION_CREATE, $log->action, '新規作成なのでcreate');
            $this->assertSame('Authors', $log->object_model, '対象モデルはAuthor');
            $this->assertSame('5', $log->object_id, '対象idは5');
            $this->assertEquals([
                'id'       => 5,
                'username' => 'foo',
            ], $log->data, '作成時のデータが記録されている');
            $this->assertArrayNotHasKey('password', $log->data, 'hiddenプロパティは記録されない。');

            // edit
            $author->isNew(false);
            $author->clean();
            $author = $this->Authors->patchEntity($author, ['username' => 'anonymous']);
            $this->Authors->save($author);

            // アクティビティログが保存されている
            $q = $this->ActivityLogs->find()->order(['id' => 'desc']);
            $this->assertCount(2, $q->all());

            $log = $q->first();
            /* @var $log ActivityLog */
            $this->assertSame(LogLevel::INFO, $log->level, 'デフォルトのログレベルはinfo');
            $this->assertSame(ActivityLog::ACTION_UPDATE, $log->action, '更新なのでUpdate');
            $this->assertSame('Authors', $log->object_model, '対象モデルはAuthor');
            $this->assertSame('5', $log->object_id, '対象idは5');
            $this->assertEquals([
                'username' => 'anonymous',
            ], $log->data, '更新時のデータが記録されている');
            $this->assertArrayNotHasKey('password', $log->data, 'hiddenプロパティは記録されない。');
        }

        public function testDelete()
        {
            $author = $this->Authors->get(1);
            $this->Authors->delete($author);
            // アクティビティログが保存されている
            $q = $this->ActivityLogs->find();
            $this->assertCount(1, $q->all());

            $log = $q->first();
            /* @var $log ActivityLog */
            $this->assertSame(LogLevel::INFO, $log->level, 'デフォルトのログレベルはinfo');
            $this->assertSame(ActivityLog::ACTION_DELETE, $log->action, '削除なのでdelete');
            $this->assertSame('Authors', $log->object_model, '対象モデルはAuthor');
            $this->assertSame('1', $log->object_id, '対象idは1');
            $this->assertEquals([
                'id'       => 1,
                'username' => 'mariano',
                'created'  => '2007-03-17T01:16:23+0900',
                'updated'  => '2007-03-17T01:18:31+0900',
            ], $log->data, '削除対象のデータが記録されている');
            $this->assertArrayNotHasKey('password', $log->data, 'hiddenプロパティは記録されない。');
        }
    }

}
