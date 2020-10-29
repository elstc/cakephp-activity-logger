<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Elastic\ActivityLogger\Model\Behavior\LoggerBehavior;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;
use Elastic\ActivityLogger\Model\Table\AlterActivityLogsTable;
use Psr\Log\LogLevel;

/**
 * Elastic\ActivityLogger\Model\Behavior\LoggerBehavior Test Case
 *
 * @property \Elastic\ActivityLogger\Model\Table\ActivityLogsTable $ActivityLogs
 * @property \Elastic\ActivityLogger\Model\Table\AuthorsTable $Authors
 * @property \Elastic\ActivityLogger\Model\Table\ArticlesTable $Articles
 * @property \Elastic\ActivityLogger\Model\Table\CommentsTable $Comments
 * @property \Elastic\ActivityLogger\Model\Table\UsersTable $Users
 * @property LoggerBehavior $Logger
 */
class LoggerBehaviorTest extends TestCase
{
    public $fixtures = [
        'plugin.Elastic/ActivityLogger.EmptyRecords\ActivityLogs',
        'plugin.Elastic/ActivityLogger.Authors',
        'plugin.Elastic/ActivityLogger.Articles',
        'plugin.Elastic/ActivityLogger.Comments',
        'plugin.Elastic/ActivityLogger.Users',
    ];

    public $dropTables = true;

    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'MyApp');
        $this->Logger = new LoggerBehavior(new \Cake\ORM\Table());
        $this->Authors = $this->getTableLocator()->get('Elastic/ActivityLogger.Authors');
        $this->Articles = $this->getTableLocator()->get('Elastic/ActivityLogger.Articles');
        $this->Comments = $this->getTableLocator()->get('Elastic/ActivityLogger.Comments');
        $this->Users = $this->getTableLocator()->get('Elastic/ActivityLogger.Users');
        $this->ActivityLogs = $this->getTableLocator()->get('Elastic/ActivityLogger.ActivityLogs');
    }

    public function tearDown(): void
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
            '\MyApp' => true,
        ], $this->Authors->getLogScope(), 'will set system scope');
        $this->assertSame([
            'Elastic/ActivityLogger.Authors' => null,
            'Elastic/ActivityLogger.Articles' => null,
            'Elastic/ActivityLogger.Users' => null,
        ], $this->Comments->getLogScope(), 'if systemScope = false, will does not set system scope');
        $this->markTestIncomplete('Not cover all');
    }

    public function testSave()
    {
        $author = $this->Authors->newEntity([
            'username' => 'foo',
            'password' => 'bar',
        ]);
        $this->Authors->save($author);
        // Saved ActivityLogs
        $q = $this->ActivityLogs->find();
        $this->assertCount(2, $q->all(), 'record two logs, that the Authors scope and the System scope');

        $log = $q->first();
        /** @var ActivityLog $log */
        $this->assertSame(LogLevel::INFO, $log->level, 'default log level is `info`');
        $this->assertSame(ActivityLog::ACTION_CREATE, $log->action, 'that `create`, it is a new creation');
        $this->assertSame('Elastic/ActivityLogger.Authors', $log->object_model, 'object model is `Author`');
        $this->assertSame('5', $log->object_id, 'object id is `5`');
        $this->assertEquals([
            'id' => 5,
            'username' => 'foo',
        ], $log->data, 'recorded the data at the time of creation');
        $this->assertArrayNotHasKey('password', $log->data, 'Does not recorded hidden values');

        // edit
        $author->setNew(false);
        $author->clean();
        $author = $this->Authors->patchEntity($author, ['username' => 'anonymous']);
        $this->Authors->save($author);

        // Saved ActivityLogs
        $q = $this->ActivityLogs->find()->order(['id' => 'desc']);
        $this->assertCount(4, $q->all(), 'record two logs, that the Authors scope and the System scope');

        $log = $q->first();
        /** @var ActivityLog $log */
        $this->assertSame(LogLevel::INFO, $log->level, 'default log level is `info`');
        $this->assertSame(ActivityLog::ACTION_UPDATE, $log->action, 'that `update`, it is a updating');
        $this->assertSame('Elastic/ActivityLogger.Authors', $log->object_model, 'object model is `Author`');
        $this->assertSame('5', $log->object_id, 'object id is `5`');
        $this->assertEquals([
            'username' => 'anonymous',
        ], $log->data, 'recorded the data at the time of updating');
        $this->assertArrayNotHasKey('password', $log->data, 'Does not recorded hidden values。');
    }

    public function testDelete()
    {
        $author = $this->Authors->get(1);
        $this->Authors->delete($author);
        // Saved ActivityLogs
        $q = $this->ActivityLogs->find();
        $this->assertCount(2, $q->all());

        $log = $q->first();
        /** @var ActivityLog $log */
        $this->assertSame(LogLevel::INFO, $log->level, 'default log level is `info`');
        $this->assertSame(ActivityLog::ACTION_DELETE, $log->action, 'that `delete`, it is a deleting');
        $this->assertSame('Elastic/ActivityLogger.Authors', $log->object_model, 'object model is `Author`');
        $this->assertSame('1', $log->object_id, 'object id is `1`');
        $this->assertEquals([
            'id' => 1,
            'username' => 'mariano',
            'created' => '2007-03-17T01:16:23+00:00',
            'updated' => '2007-03-17T01:18:31+00:00',
        ], $log->data, 'recorded the data at the time of deleting');
        $this->assertArrayNotHasKey('password', $log->data, 'Does not recorded hidden values。');
    }

    public function testLogScope()
    {
        $this->assertSame([
            'Elastic/ActivityLogger.Authors' => null,
            '\MyApp' => true,
        ], $this->Authors->getLogScope(), 'can get log scope');
        $this->assertSame([
            'Elastic/ActivityLogger.Articles' => null,
            'Elastic/ActivityLogger.Authors' => null,
            '\MyApp' => true,
        ], $this->Articles->getLogScope(), 'can get log scope');

        // Set and get
        $author = $this->Authors->get(1);
        $this->Authors->setLogScope($author);
        $this->assertSame([
            'Elastic/ActivityLogger.Authors' => 1,
            '\MyApp' => true,
        ], $this->Authors->getLogScope(), 'updated log scope');
        $article = $this->Articles->get(2);
        $this->Articles->setLogScope([$article, $author]);
        $this->assertSame([
            'Elastic/ActivityLogger.Articles' => 2,
            'Elastic/ActivityLogger.Authors' => 1,
            '\MyApp' => true,
        ], $this->Articles->getLogScope(), 'can get log scope');

        // Add scope
        $this->Articles->setLogScope($this->Comments->get(3));
        $this->Articles->setLogScope('Custom');
        $this->Articles->setLogScope(['Another' => 4, 'Foo' => '005', 'Hoge']);
        $this->assertSame([
            'Elastic/ActivityLogger.Articles' => 2,
            'Elastic/ActivityLogger.Authors' => 1,
            '\MyApp' => true,
            'Elastic/ActivityLogger.Comments' => 3,
            'Custom' => true,
            'Another' => 4,
            'Foo' => '005',
            'Hoge' => true,
        ], $this->Articles->getLogScope(), 'updated log scope');
        // Reset scope
        $this->Articles->setLogScope(false);
        $this->assertSame([
            'Elastic/ActivityLogger.Articles' => null,
            'Elastic/ActivityLogger.Authors' => null,
            '\MyApp' => true,
        ], $this->Articles->getLogScope(), 'will reset log scope');
    }

    public function testLogScopeSetterGetter()
    {
        $this->assertSame([
            'Elastic/ActivityLogger.Authors' => null,
            '\MyApp' => true,
        ], $this->Authors->getLogScope(), 'can get log scope');
        $this->assertSame([
            'Elastic/ActivityLogger.Articles' => null,
            'Elastic/ActivityLogger.Authors' => null,
            '\MyApp' => true,
        ], $this->Articles->getLogScope(), 'can get log scope');

        // Set and get
        $author = $this->Authors->get(1);
        $this->Authors->setLogScope($author);
        $this->assertSame([
            'Elastic/ActivityLogger.Authors' => 1,
            '\MyApp' => true,
        ], $this->Authors->getLogScope(), 'updated log scope');
        $article = $this->Articles->get(2);
        $this->Articles->setLogScope([$article, $author]);
        $this->assertSame([
            'Elastic/ActivityLogger.Articles' => 2,
            'Elastic/ActivityLogger.Authors' => 1,
            '\MyApp' => true,
        ], $this->Articles->getLogScope(), 'can get log scope');

        // Add scope
        $this->Articles->setLogScope($this->Comments->get(3));
        $this->Articles->setLogScope('Custom');
        $this->Articles->setLogScope(['Another' => 4, 'Foo' => '005', 'Hoge']);
        $this->assertSame([
            'Elastic/ActivityLogger.Articles' => 2,
            'Elastic/ActivityLogger.Authors' => 1,
            '\MyApp' => true,
            'Elastic/ActivityLogger.Comments' => 3,
            'Custom' => true,
            'Another' => 4,
            'Foo' => '005',
            'Hoge' => true,
        ], $this->Articles->getLogScope(), 'will reset log scope');
        // Reset scope
        $this->Articles->setLogScope(false);
        $this->assertSame([
            'Elastic/ActivityLogger.Articles' => null,
            'Elastic/ActivityLogger.Authors' => null,
            '\MyApp' => true,
        ], $this->Articles->getLogScope(), 'will reset log scope');
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
        /** @var ActivityLog $log */
        $this->assertEquals($author->id, $log->scope_id, 'will set scope');
        $log = $this->ActivityLogs->find()
            ->where(['scope_model' => '\MyApp'])
            ->order(['id' => 'desc'])->first();
        /** @var ActivityLog $log */
        $this->assertEquals(1, $log->scope_id, 'will set scope');

        $article = $this->Articles->get(2);
        $user = $this->Users->get(1);
        $comment = $this->Comments->newEntity([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'comment' => 'Awesome!',
        ]);
        $this->Comments->setLogScope([$article, $user]);
        $this->Comments->save($comment);

        $logs = $this->ActivityLogs->find()
            ->where(['object_model' => 'Elastic/ActivityLogger.Comments'])
            ->order(['id' => 'desc'])
            ->all()
            ->toArray();

        $this->assertCount(2, $logs);
        $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->scope_model, 'will set scope model');
        $this->assertEquals($user->id, $logs[0]->scope_id, 'will set scope');
        $this->assertSame('Elastic/ActivityLogger.Articles', $logs[1]->scope_model, 'will set scope model');
        $this->assertEquals($article->id, $logs[1]->scope_id, 'will set scope');
    }

    public function testSaveWithScopeMap()
    {
        $article = $this->Articles->get(2);
        $user = $this->Users->get(1);
        $comment = $this->Comments->newEntity([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'comment' => 'Awesome!',
        ]);
        $this->Comments->behaviors()->get('Logger')->setConfig('scopeMap', [
            'article_id' => 'Elastic/ActivityLogger.Articles',
            'user_id' => 'Elastic/ActivityLogger.Users',
        ]);
        $this->Comments->save($comment);

        $logs = $this->ActivityLogs->find()
            ->where(['object_model' => 'Elastic/ActivityLogger.Comments'])
            ->order(['id' => 'desc'])
            ->all()
            ->toArray();

        $this->assertCount(2, $logs);
        $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->scope_model, 'will set scope model');
        $this->assertEquals($user->id, $logs[0]->scope_id, 'will set scope');
        $this->assertSame('Elastic/ActivityLogger.Articles', $logs[1]->scope_model, 'will set scope model');
        $this->assertEquals($article->id, $logs[1]->scope_id, 'will set scope id');
    }

    public function testSaveWithIssuer()
    {
        $user = $this->Users->get(1);
        $this->Authors->setLogIssuer($user);
        $author = $this->Authors->newEntity([
            'username' => 'foo',
            'password' => 'bar',
        ]);
        $this->Authors->save($author);
        $log = $this->ActivityLogs->find()->order(['id' => 'desc'])->first();
        /** @var ActivityLog $log */
        $this->assertSame('Elastic/ActivityLogger.Users', $log->issuer_model, 'will set issuer model');
        $this->assertEquals($user->id, $log->issuer_id, '発行者が指定されている');

        $article = $this->Articles->get(2);
        $user = $this->Users->get(1);
        $comment = $this->Comments->newEntity([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'comment' => 'Awesome!',
        ]);
        $this->Comments->setLogIssuer($user);
        $this->Comments->setLogScope($article);
        $this->Comments->save($comment);

        $logs = $this->ActivityLogs->find()
            ->where(['object_model' => 'Elastic/ActivityLogger.Comments'])
            ->order(['id' => 'desc'])
            ->all()
            ->toArray();

        $this->assertCount(2, $logs);
        $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->scope_model, 'will set scope model from issuer');
        $this->assertEquals($user->id, $logs[0]->scope_id, 'will set scope id from issuer');
        $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->issuer_model, 'will set issuer model');
        $this->assertEquals($user->id, $logs[0]->issuer_id, 'will set issuer id');
        $this->assertSame('Elastic/ActivityLogger.Articles', $logs[1]->scope_model, 'will set scope model');
        $this->assertEquals($article->id, $logs[1]->scope_id, 'will set scope id');
        $this->assertSame('Elastic/ActivityLogger.Users', $logs[1]->issuer_model, 'will set issuer model');
        $this->assertEquals($user->id, $logs[1]->issuer_id, 'will set issuer id');
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
            'scope' => [$article, $author],
            'object' => $this->Comments->get(2),
            'action' => 'publish',
        ];
        $this->Comments->activityLog($level, $message, $context);

        $logs = $this->ActivityLogs->find()
            ->order(['id' => 'desc'])
            ->all()
            ->toArray();

        $this->assertCount(3, $logs);
        $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->scope_model, 'will set scope model');
        $this->assertEquals($user->id, $logs[0]->scope_id, 'will set scope id');
        $this->assertSame('Elastic/ActivityLogger.Users', $logs[0]->issuer_model, 'will set issuer model');
        $this->assertEquals($user->id, $logs[0]->issuer_id, 'will set issuer id');
        $this->assertSame('Elastic/ActivityLogger.Comments', $logs[0]->object_model);
        $this->assertEquals('2', $logs[0]->object_id);
        $this->assertSame($message, $logs[0]->message);
        $this->assertSame($level, $logs[0]->level);
        $this->assertSame('publish', $logs[0]->action);

        $this->assertSame('Elastic/ActivityLogger.Authors', $logs[1]->scope_model, 'will set scope model');
        $this->assertEquals($article->id, $logs[1]->scope_id, 'will set scope id');
        $this->assertSame('Elastic/ActivityLogger.Users', $logs[1]->issuer_model, 'will set issuer model');
        $this->assertEquals($user->id, $logs[1]->issuer_id, 'will set issuer id');
        $this->assertSame('Elastic/ActivityLogger.Comments', $logs[1]->object_model);
        $this->assertEquals('2', $logs[1]->object_id);
        $this->assertSame($message, $logs[1]->message);
        $this->assertSame($level, $logs[1]->level);
        $this->assertSame('publish', $logs[1]->action);

        $this->assertSame('Elastic/ActivityLogger.Articles', $logs[2]->scope_model, 'will set scope model');
        $this->assertEquals($article->id, $logs[2]->scope_id, 'will set scope id');
        $this->assertSame('Elastic/ActivityLogger.Users', $logs[2]->issuer_model, 'will set issuer model');
        $this->assertEquals($user->id, $logs[2]->issuer_id, 'will set issuer id');
        $this->assertSame('Elastic/ActivityLogger.Comments', $logs[2]->object_model);
        $this->assertEquals('2', $logs[2]->object_id);
        $this->assertSame($message, $logs[2]->message);
        $this->assertSame($level, $logs[2]->level);
        $this->assertSame('publish', $logs[2]->action);
    }

    public function testLogMessageBuilder()
    {
        $this->assertNull($this->Articles->getLogMessageBuilder());
        $this->Articles->setLogMessageBuilder(function (ActivityLog $log, array $context) {
            if (!empty($log->message)) {
                return $log->message;
            }

            $message = '';
            $object = $context['object'] ?: null;
            $issuer = $context['issuer'] ?: null;
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
                default:
                    break;
            }

            return $message;
        });

        // Create new article
        $author = $this->Authors->get(1);
        $article = $this->Articles->newEntity([
            'title' => 'バージョン1.0リリース',
            'body' => '新しいバージョン 1.0 をリリースしました。',
            'author' => $author,
        ]);
        /** @var \Elastic\ActivityLogger\Model\Entity\Article $article */
        $this->Articles->setLogIssuer($author);
        $this->Articles->save($article);

        // Update the article
        $article->title = 'バージョン1.0 stableリリース';
        $this->Articles->save($article);

        // Record custom log
        $this->Articles->activityLog(LogLevel::NOTICE, '記事を更新しています。');

        // Deleting by another user
        $this->Articles->setLogIssuer($this->Authors->get(2));
        $this->Articles->delete($article);

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

    public function testLogMessageBuilderSetterGetter()
    {
        $this->assertNull($this->Articles->getLogMessageBuilder());
        $this->Articles->setLogMessageBuilder(function (ActivityLog $log, array $context) {
            if (!empty($log->message)) {
                return $log->message;
            }

            $message = '';
            $object = $context['object'] ?: null;
            $issuer = $context['issuer'] ?: null;
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
                default:
                    break;
            }

            return $message;
        });

        // Create new article
        $author = $this->Authors->get(1);
        $article = $this->Articles->newEntity([
            'title' => 'バージョン1.0リリース',
            'body' => '新しいバージョン 1.0 をリリースしました。',
            'author' => $author,
        ]);
        /** @var \Elastic\ActivityLogger\Model\Entity\Article $article */
        $this->Articles->setLogIssuer($author);
        $this->Articles->save($article);

        // Update the article
        $article->title = 'バージョン1.0 stableリリース';
        $this->Articles->save($article);

        // Record custom log
        $this->Articles->activityLog(LogLevel::NOTICE, '記事を更新しています。');

        // Deleting by another user
        $this->Articles->setLogIssuer($this->Authors->get(2));
        $this->Articles->delete($article);

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

    public function testSetLogMessage()
    {
        $author = $this->Authors->get(1);
        $this->Articles->setLogIssuer($author);

        // Create new article
        $this->Articles->setLogMessage('custom message');
        $article = $this->Articles->newEntity([
            'title' => 'バージョン1.0リリース',
            'body' => '新しいバージョン 1.0 をリリースしました。',
            'author' => $author,
        ]);
        /** @var \Elastic\ActivityLogger\Model\Entity\Article $article */
        $this->Articles->save($article);

        // Update the article
        $article->title = 'バージョン1.0 stableリリース';
        $this->Articles->save($article);

        // Update the article
        $this->Articles->setLogMessage('persist custom message', true);
        $article->title = 'バージョン1.0.0 stableリリース';
        $this->Articles->save($article);
        // Delete the article
        $this->Articles->delete($article);

        $logs = $this->ActivityLogs->find()
            ->where(['scope_model' => 'Elastic/ActivityLogger.Authors'])
            ->order(['id' => 'asc'])
            ->all()
            ->toArray();

        $this->assertCount(4, $logs);
        $this->assertSame('custom message', $logs[0]->message);
        $this->assertSame('', $logs[1]->message, 'reset message that set from `setLogMessage`, when any log recorded');
        $this->assertSame('persist custom message', $logs[2]->message);
        $this->assertSame('persist custom message', $logs[3]->message, 'keep message, when persist flag is true');
    }

    public function testFindActivity()
    {
        $author = $this->Authors->get(1);
        $article = $this->Articles->newEntity([
            'title' => 'new article',
            'body' => 'new content.',
            'author' => $author,
        ]);
        $this->Articles->setLogIssuer($author)->save($article);
        $user = $this->Users->get(2);
        $comment = $this->Comments->newEntity([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'comment' => 'new comment',
        ]);
        $this->Comments->setLogIssuer($user)->setLogScope([$article])->save($comment);

        $authorLogs = $this->Authors->find('activity', ['scope' => $author])
            ->all()
            ->toArray();
        $this->assertCount(1, $authorLogs);
        $this->assertSame('Elastic/ActivityLogger.Articles', $authorLogs[0]->object_model);
        $articleLogs = $this->Articles->find('activity', ['scope' => $article])
            ->all()
            ->toArray();
        $this->assertCount(2, $articleLogs);
        $this->assertSame('Elastic/ActivityLogger.Comments', $articleLogs[0]->object_model, 'The latest one is displayed above');
        $this->assertSame('Elastic/ActivityLogger.Articles', $articleLogs[1]->object_model);
        $commentLogs = $this->Comments->find('activity', ['scope' => $comment])
            ->all()
            ->toArray();
        $this->assertCount(0, $commentLogs);
        $userLogs = $this->Users->find('activity', ['scope' => $user])
            ->all()
            ->toArray();
        $this->assertCount(1, $userLogs);
        $this->assertSame('Elastic/ActivityLogger.Comments', $userLogs[0]->object_model);
    }

    public function testAnotherLogModel()
    {
        $this->Users->activityLog(LogLevel::DEBUG, 'test log');

        $this->Logger->setConfig('logModel', AlterActivityLogsTable::class);
        $this->Logger->setConfig('logModelAlias', 'AlterActivityLogs');
        $this->Logger->activityLog(LogLevel::DEBUG, 'alter test log');

        $this->assertTrue(true, 'Do not throws any exception');
    }
}
