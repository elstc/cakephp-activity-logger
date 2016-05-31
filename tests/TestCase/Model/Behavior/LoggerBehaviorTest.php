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

            $this->addBehavior('Elastic/ActivityLogger.Logger', [
                'scope' => [
                    'Elastic/ActivityLogger.Authors',
                    'Elastic/ActivityLogger.Articles',
                    'Elastic/ActivityLogger.Users',
                ],
            ]);
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
     * @property \Elastic\ActivityLogger\Model\Table\UsersTable $Users
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
            $this->Authors = TableRegistry::get('Elastic/ActivityLogger.Authors');
            $this->Articles = TableRegistry::get('Elastic/ActivityLogger.Articles');
            $this->Comments = TableRegistry::get('Elastic/ActivityLogger.Comments');
            $this->Users = TableRegistry::get('Elastic/ActivityLogger.Users');
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
            $this->assertSame('Elastic/ActivityLogger.Authors', $log->object_model, '対象モデルはAuthor');
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
            $this->assertSame('Elastic/ActivityLogger.Authors', $log->object_model, '対象モデルはAuthor');
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
            $this->assertSame('Elastic/ActivityLogger.Authors', $log->object_model, '対象モデルはAuthor');
            $this->assertSame('1', $log->object_id, '対象idは1');
            $this->assertEquals([
                'id'       => 1,
                'username' => 'mariano',
                'created'  => '2007-03-17T01:16:23+0900',
                'updated'  => '2007-03-17T01:18:31+0900',
            ], $log->data, '削除対象のデータが記録されている');
            $this->assertArrayNotHasKey('password', $log->data, 'hiddenプロパティは記録されない。');
        }

        public function testLogScope()
        {
            $this->assertSame([
                'Elastic/ActivityLogger.Authors' => null,
            ], $this->Authors->logScope(), 'ログのスコープが取得できる');
            //
            $this->assertSame([
                'Elastic/ActivityLogger.Articles' => null,
                'Elastic/ActivityLogger.Authors'  => null,
            ], $this->Articles->logScope(), 'ログのスコープが取得できる');

            // セットして取得
            $author = $this->Authors->get(1);
            $this->Authors->logScope($author);
            $this->assertSame([
                'Elastic/ActivityLogger.Authors' => 1,
            ], $this->Authors->logScope(), 'ログのスコープが更新されている');
            //
            $article = $this->Articles->get(2);
            $this->Articles->logScope([$article, $author]);
            $this->assertSame([
                'Elastic/ActivityLogger.Articles' => 2,
                'Elastic/ActivityLogger.Authors'  => 1,
            ], $this->Articles->logScope(), 'ログのスコープが取得できる');

            // スコープの追加
            $this->Articles->logScope($this->Comments->get(3));
            $this->assertSame([
                'Elastic/ActivityLogger.Articles' => 2,
                'Elastic/ActivityLogger.Authors'  => 1,
                'Elastic/ActivityLogger.Comments' => 3,
            ], $this->Articles->logScope(), 'ログのスコープが取得できる');
            // スコープのリセット
            $this->Articles->logScope(false);
            $this->assertSame([
                'Elastic/ActivityLogger.Articles' => null,
                'Elastic/ActivityLogger.Authors'  => null,
            ], $this->Articles->logScope(), 'ログのスコープがリセットされている');
        }

        public function testSaveWithScope()
        {
            $author = $this->Authors->newEntity([
                'username' => 'foo',
                'password' => 'bar',
            ]);
            $this->Authors->save($author);
            $log = $this->ActivityLogs->find()->order(['id' => 'desc'])->first();
            /* @var $log ActivityLog */
            $this->assertSame('Elastic/ActivityLogger.Authors', $log->scope_model, 'スコープが指定されている');
            $this->assertEquals($author->id, $log->scope_id, 'スコープが指定されている');

            //
            $article = $this->Articles->get(2);
            $user = $this->Users->get(1);
            $comment = $this->Comments->newEntity([
                'article_id' => $article->id,
                'user_id'    => $user->id,
                'comment'    => 'Awesome!',
            ]);
            $this->Comments->logScope([$article, $user]);
            $this->Comments->save($comment);

            $logs = $this->ActivityLogs->find()
            ->where(['object_model' => 'Elastic/ActivityLogger.Comments'])
            ->order(['id' => 'desc'])
            ->all()
            ->toArray();

            $this->assertCount(2, $logs);
            $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->scope_model, 'スコープが指定されている');
            $this->assertEquals($user->id, $logs[0]->scope_id, 'スコープが指定されている');
            $this->assertSame('Elastic/ActivityLogger.Articles', $logs[1]->scope_model, 'スコープが指定されている');
            $this->assertEquals($article->id, $logs[1]->scope_id, 'スコープが指定されている');
        }

        public function testSaveWithIssuer()
        {
            $user = $this->Users->get(1);
            $this->Authors->logIssuer($user);
            $author = $this->Authors->newEntity([
                'username' => 'foo',
                'password' => 'bar',
            ]);
            $this->Authors->save($author);
            $log = $this->ActivityLogs->find()->order(['id' => 'desc'])->first();
            /* @var $log ActivityLog */
            $this->assertSame('Elastic/ActivityLogger.Users', $log->issuer_model, '発行者が指定されている');
            $this->assertEquals($user->id, $log->issuer_id, '発行者が指定されている');

            //
            $article = $this->Articles->get(2);
            $user = $this->Users->get(1);
            $comment = $this->Comments->newEntity([
                'article_id' => $article->id,
                'user_id'    => $user->id,
                'comment'    => 'Awesome!',
            ]);
            $this->Comments->logIssuer($user);
            $this->Comments->logScope($article);
            $this->Comments->save($comment);

            $logs = $this->ActivityLogs->find()
            ->where(['object_model' => 'Elastic/ActivityLogger.Comments'])
            ->order(['id' => 'desc'])
            ->all()
            ->toArray();

            $this->assertCount(2, $logs);
            $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->scope_model, '発行者からスコープが指定されている');
            $this->assertEquals($user->id, $logs[0]->scope_id, '発行者からスコープが指定されている');
            $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->issuer_model, '発行者が指定されている');
            $this->assertEquals($user->id, $logs[0]->issuer_id, '発行者が指定されている');
            $this->assertSame('Elastic/ActivityLogger.Articles', $logs[1]->scope_model, 'スコープが指定されている');
            $this->assertEquals($article->id, $logs[1]->scope_id, 'スコープが指定されている');
            $this->assertSame('Elastic/ActivityLogger.Users', $logs[1]->issuer_model, '発行者が指定されている');
            $this->assertEquals($user->id, $logs[1]->issuer_id, '発行者が指定されている');
        }

        public function testActivityLog()
        {
            $level = LogLevel::WARNING;
            $message = 'custom message';
            $user = $this->Users->get(4);
            $article = $this->Articles->get(1);
            $author = $this->Authors->get(1);
            $context = [
                'issuer' => $user,
                'scope'  => [$article, $author],
                'object' => $this->Comments->get(2),
                'action' => 'publish',
            ];
            $this->Comments->activityLog($level, $message, $context);

            $logs = $this->ActivityLogs->find()
            ->order(['id' => 'desc'])
            ->all()
            ->toArray();

            $this->assertCount(3, $logs);
            $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->scope_model, '発行者からスコープが指定されている');
            $this->assertEquals($user->id, $logs[0]->scope_id, '発行者からスコープが指定されている');
            $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->issuer_model, '発行者が指定されている');
            $this->assertEquals($user->id, $logs[0]->issuer_id, '発行者が指定されている');
            $this->assertSame('Elastic/ActivityLogger.Comments', $logs[0]->object_model);
            $this->assertEquals('2', $logs[0]->object_id);
            $this->assertSame($message, $logs[0]->message);
            $this->assertSame($level, $logs[0]->level);
            $this->assertSame('publish', $logs[0]->action);

            $this->assertSame('Elastic/ActivityLogger.Authors', $logs[1]->scope_model, 'スコープが指定されている');
            $this->assertEquals($article->id, $logs[1]->scope_id, 'スコープが指定されている');
            $this->assertSame('Elastic/ActivityLogger.Users', $logs[1]->issuer_model, '発行者が指定されている');
            $this->assertEquals($user->id, $logs[1]->issuer_id, '発行者が指定されている');
            $this->assertSame('Elastic/ActivityLogger.Comments', $logs[1]->object_model);
            $this->assertEquals('2', $logs[1]->object_id);
            $this->assertSame($message, $logs[1]->message);
            $this->assertSame($level, $logs[1]->level);
            $this->assertSame('publish', $logs[1]->action);

            $this->assertSame('Elastic/ActivityLogger.Articles', $logs[2]->scope_model, 'スコープが指定されている');
            $this->assertEquals($article->id, $logs[2]->scope_id, 'スコープが指定されている');
            $this->assertSame('Elastic/ActivityLogger.Users', $logs[2]->issuer_model, '発行者が指定されている');
            $this->assertEquals($user->id, $logs[2]->issuer_id, '発行者が指定されている');
            $this->assertSame('Elastic/ActivityLogger.Comments', $logs[2]->object_model);
            $this->assertEquals('2', $logs[2]->object_id);
            $this->assertSame($message, $logs[2]->message);
            $this->assertSame($level, $logs[2]->level);
            $this->assertSame('publish', $logs[2]->action);
        }
    }

}
