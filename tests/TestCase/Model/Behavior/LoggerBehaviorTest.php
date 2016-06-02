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

namespace Elastic\ActivityLogger\Test\TestCase\Model\Behavior {

    use Cake\Core\Configure;
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

        public $fixtures = [
            'plugin.Elastic/ActivityLogger.ActivityLogs',
            'plugin.Elastic/ActivityLogger.Authors',
            'plugin.Elastic/ActivityLogger.Articles',
            'plugin.Elastic/ActivityLogger.Comments',
            'plugin.Elastic/ActivityLogger.Users',
        ];

        public $dropTables = true;

        public function setUp()
        {
            parent::setUp();
            Configure::write('App.namespace', 'MyApp');
            $this->Logger = new LoggerBehavior(new \Cake\ORM\Table);
            $this->Authors = TableRegistry::get('Elastic/ActivityLogger.Authors');
            $this->Articles = TableRegistry::get('Elastic/ActivityLogger.Articles');
            $this->Comments = TableRegistry::get('Elastic/ActivityLogger.Comments');
            $this->Users = TableRegistry::get('Elastic/ActivityLogger.Users');
            $this->ActivityLogs = TableRegistry::get('Elastic/ActivityLogger.ActivityLogs');
        }

        public function tearDown()
        {
            unset($this->Logger);
            unset($this->Authors);
            unset($this->Articles);
            unset($this->Users);
            unset($this->Comments);
            unset($this->ActivityLogs);

            parent::tearDown();
        }

        /**
         * Test initial setup
         *
         * @return void
         */
        public function testInitialization()
        {
            $this->assertSame([
                'Elastic/ActivityLogger.Authors' => null,
                '\MyApp'                         => true,
                ], $this->Authors->logScope(), 'システムスコープがセットされている');
            $this->assertSame([
                'Elastic/ActivityLogger.Authors'  => null,
                'Elastic/ActivityLogger.Articles' => null,
                'Elastic/ActivityLogger.Users'    => null,
                ], $this->Comments->logScope(), 'systemScope = false ならばシステムスコープはセットされない');
            $this->markTestIncomplete('Not cover all');
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
            $this->assertCount(2, $q->all(), 'Authorsスコープとシステムスコープでログは2つ作成される');

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
            $this->assertCount(4, $q->all(), 'Authorsスコープとシステムスコープでログは2つ作成される');

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
            $this->assertCount(2, $q->all());

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
                '\MyApp'                         => true,
                ], $this->Authors->logScope(), 'ログのスコープが取得できる');
            //
            $this->assertSame([
                'Elastic/ActivityLogger.Articles' => null,
                'Elastic/ActivityLogger.Authors'  => null,
                '\MyApp'                          => true,
                ], $this->Articles->logScope(), 'ログのスコープが取得できる');

            // セットして取得
            $author = $this->Authors->get(1);
            $this->Authors->logScope($author);
            $this->assertSame([
                'Elastic/ActivityLogger.Authors' => 1,
                '\MyApp'                         => true,
                ], $this->Authors->logScope(), 'ログのスコープが更新されている');
            //
            $article = $this->Articles->get(2);
            $this->Articles->logScope([$article, $author]);
            $this->assertSame([
                'Elastic/ActivityLogger.Articles' => 2,
                'Elastic/ActivityLogger.Authors'  => 1,
                '\MyApp'                          => true,
                ], $this->Articles->logScope(), 'ログのスコープが取得できる');

            // スコープの追加
            $this->Articles->logScope($this->Comments->get(3));
            $this->Articles->logScope('Custom');
            $this->Articles->logScope(['Another' => 4, 'Foo' => '005', 'Hoge']);
            $this->assertSame([
                'Elastic/ActivityLogger.Articles' => 2,
                'Elastic/ActivityLogger.Authors'  => 1,
                '\MyApp'                          => true,
                'Elastic/ActivityLogger.Comments' => 3,
                'Custom' => true,
                'Another' => 4,
                'Foo' => '005',
                'Hoge' => true,
                ], $this->Articles->logScope(), 'ログのスコープがセットされている');
            // スコープのリセット
            $this->Articles->logScope(false);
            $this->assertSame([
                'Elastic/ActivityLogger.Articles' => null,
                'Elastic/ActivityLogger.Authors'  => null,
                '\MyApp'                          => true,
                ], $this->Articles->logScope(), 'ログのスコープがリセットされている');
        }

        public function testSaveWithScope()
        {
            $author = $this->Authors->newEntity([
                'username' => 'foo',
                'password' => 'bar',
            ]);
            $this->Authors->save($author);
            $log = $this->ActivityLogs->find()
                    ->where(['scope_model' => 'Elastic/ActivityLogger.Authors'])
                    ->order(['id' => 'desc'])->first();
            /* @var $log ActivityLog */
            $this->assertEquals($author->id, $log->scope_id, 'スコープが指定されている');
            $log = $this->ActivityLogs->find()
                    ->where(['scope_model' => '\MyApp'])
                    ->order(['id' => 'desc'])->first();
            /* @var $log ActivityLog */
            $this->assertEquals(1, $log->scope_id, 'スコープが指定されている');

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

        public function testLogMessageBuilder()
        {
            $this->assertNull($this->Articles->logMessageBuilder());
            //
            $this->Articles->logMessageBuilder(function (ActivityLog $log, array $context) {
                if (!empty($log->message)) {
                    return $log->message;
                }

                $message = '';
                $object = $context['object'] ? : null;
                $issuer = $context['issuer'] ? : null;
                switch ($log->action) {
                    case ActivityLog::ACTION_CREATE:
                        $message = sprintf('%3$s が記事 #%1$s「%2$s」を作成しました。', $object->id, $object->title, $issuer->username);
                        break;
                    case ActivityLog::ACTION_UPDATE:
                        $message = sprintf('%3$s が記事 #%1$s「%2$s」を更新しました。', $object->id, $object->title, $issuer->username);
                        break;
                    case ActivityLog::ACTION_DELETE:
                        $message = sprintf('%3$s が記事 #%1$s「%2$s」を削除しました。', $object->id, $object->title, $issuer->username);
                        break;
                    default :
                        break;
                }
                return $message;
            });

            // 記事を新規作成
            $author = $this->Authors->get(1);
            $article = $this->Articles->newEntity([
                'title'  => 'バージョン1.0リリース',
                'body'   => '新しいバージョン 1.0 をリリースしました。',
                'author' => $author,
            ]);
            $this->Articles->logIssuer($author)->save($article);

            // 記事を更新
            $article->title = 'バージョン1.0 stableリリース';
            $this->Articles->save($article);

            // 任意のログ
            $this->Articles->activityLog(LogLevel::NOTICE, '記事を更新しています。');

            // 別のユーザーが削除
            $this->Articles->logIssuer($this->Authors->get(2))->delete($article);

            $logs = $this->ActivityLogs->find()
                ->where(['scope_model' => 'Elastic/ActivityLogger.Authors'])
                ->order(['id' => 'asc'])
                ->all()
                ->toArray();

            $this->assertCount(4, $logs);
            $this->assertSame('mariano が記事 #4「バージョン1.0リリース」を作成しました。', $logs[0]->message);
            $this->assertSame('mariano が記事 #4「バージョン1.0 stableリリース」を更新しました。', $logs[1]->message);
            $this->assertSame('記事を更新しています。', $logs[2]->message);
            $this->assertSame('nate が記事 #4「バージョン1.0 stableリリース」を削除しました。', $logs[3]->message);
        }

        public function testFindActivity()
        {
            $author = $this->Authors->get(1);
            $article = $this->Articles->newEntity([
                'title'  => 'new article',
                'body'   => 'new content.',
                'author' => $author,
            ]);
            $this->Articles->logIssuer($author)->save($article);
            $user = $this->Users->get(2);
            $comment = $this->Comments->newEntity([
                'user_id'    => $user->id,
                'article_id' => $article->id,
                'comment'    => 'new comment',
            ]);
            $this->Comments->logIssuer($user)->logScope([$article])->save($comment);

            //
            $authorLogs = $this->Authors->find('activity', ['scope' => $author])
                ->all()
                ->toArray();
            $this->assertCount(1, $authorLogs);
            $this->assertSame('Elastic/ActivityLogger.Articles', $authorLogs[0]->object_model);
            //
            $articleLogs = $this->Articles->find('activity', ['scope' => $article])
                ->all()
                ->toArray();
            $this->assertCount(2, $articleLogs);
            $this->assertSame('Elastic/ActivityLogger.Comments', $articleLogs[0]->object_model, '最新のものが上に表示される');
            $this->assertSame('Elastic/ActivityLogger.Articles', $articleLogs[1]->object_model);
            //
            $commentLogs = $this->Comments->find('activity', ['scope' => $comment])
                ->all()
                ->toArray();
            $this->assertCount(0, $commentLogs);
            //
            $userLogs = $this->Users->find('activity', ['scope' => $user])
                ->all()
                ->toArray();
            $this->assertCount(1, $userLogs);
            $this->assertSame('Elastic/ActivityLogger.Comments', $userLogs[0]->object_model);
        }
    }

}
