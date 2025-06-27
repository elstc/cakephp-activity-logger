# ActivityLogger plugin for CakePHP 5.x

ActivityLoggerプラグインは、CakePHPアプリケーションでのデータベース操作（作成・更新・削除）のログを自動的に記録するプラグインです。誰が・いつ・何を変更したかを追跡できます。

<p style="text-align: center">
    <a href="LICENSE.txt" target="_blank">
        <img alt="Software License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square">
    </a>
    <a href="https://github.com/elstc/cakephp-activity-logger/actions" target="_blank">
        <img alt="Build Status" src="https://img.shields.io/github/actions/workflow/status/elstc/cakephp-activity-logger/ci.yml?style=flat-square">
    </a>
    <a href="https://codecov.io/gh/elstc/cakephp-activity-logger" target="_blank">
        <img alt="Codecov" src="https://img.shields.io/codecov/c/github/elstc/cakephp-activity-logger.svg?style=flat-square">
    </a>
    <a href="https://packagist.org/packages/elstc/cakephp-activity-logger" target="_blank">
        <img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/elstc/cakephp-activity-logger.svg?style=flat-square">
    </a>
</p>

## 要件

- PHP 8.1以上
- CakePHP 5.0以上
- PDO拡張
- JSON拡張

## インストール

[Composer](http://getcomposer.org) を使用してCakePHPアプリケーションにこのプラグインをインストールできます。

推奨されるComposerパッケージのインストール方法：

```
composer require elstc/cakephp-activity-logger:^3.0
```

### プラグインの読み込み

プロジェクトの`src/Application.php`に以下の文を追加してプラグインを読み込みます：

```php
$this->addPlugin('Elastic/ActivityLogger');
```

### activity_logsテーブルの作成

マイグレーションコマンドを実行します：

```
bin/cake migrations migrate -p Elastic/ActivityLogger
```

## 使用方法

### テーブルへのアタッチ

ActivityLoggerプラグインをテーブルにアタッチして、自動ログ記録を有効にします：

```php
class ArticlesTable extends Table
{
    public function initialize(array $config): void
    {
        // ...

        $this->addBehavior('Elastic/ActivityLogger.Logger', [
            'scope' => [
                'Articles',
                'Authors',
            ],
        ]);
    }
}
```

### 基本的なアクティビティログ

#### 作成時のログ記録
```php
$article = $this->Articles->newEntity([ /* データ */ ]);
$this->Articles->save($article);
// 保存されるログ
// [action='create', scope_model='Articles', scope_id=$article->id]
```

#### 更新時のログ記録
```php
$article = $this->Articles->patchEntity($article, [ /* 更新データ */ ]);
$this->Articles->save($article);
// 保存されるログ
// [action='update', scope_model='Articles', scope_id=$article->id]
```

#### 削除時のログ記録
```php
$article = $this->Articles->get($id);
$this->Articles->delete($article);
// 保存されるログ
// [action='delete', scope_model='Articles', scope_id=$article->id]
```

### 実行者（Issuer）付きアクティビティログ

操作を実行したユーザーの情報をログに記録できます：

```php
$this->Articles->setLogIssuer($author); // 実行者を設定

$article = $this->Articles->newEntity([ /* データ */ ]);
$this->Articles->save($article);

// 保存されるログ
// [action='create', scope_model='Articles', scope_id=$article->id, ...]
// および
// [action='create', scope_model='Authors', scope_id=$author->id, ...]
```

#### AutoIssuerMiddleware（CakePHP 4.x以降推奨）

`AutoIssuerMiddleware`は、`Authorization`プラグインを使用しているアプリケーションで実行者の自動設定を提供するPSR-15準拠のミドルウェアです。
このミドルウェアはアプリケーションレベルで動作し、リクエストライフサイクルの早い段階で認証情報を処理します。

##### インストールと設定

```php
// src/Application.php内
use Elastic\ActivityLogger\Http\Middleware\AutoIssuerMiddleware;

class Application extends BaseApplication
{
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // ... 他のミドルウェア
            ->add(new AuthenticationMiddleware($this))
            
            // **認証ミドルウェアの後に** AutoIssuerMiddlewareを追加
            ->add(new AutoIssuerMiddleware([
                'userModel' => 'Users',           // ユーザーモデル名（デフォルト: 'Users'）
                'identityAttribute' => 'identity', // リクエスト属性名（デフォルト: 'identity'）
            ]))
            
            // ... 他のミドルウェア
            ->add(new RoutingMiddleware($this));
            
        return $middlewareQueue;
    }
}
```

##### 重要な注意事項

- **ミドルウェアの順序**: AutoIssuerMiddlewareは必ず認証ミドルウェアの後に配置してください

#### AutoIssuerComponent（レガシーアプローチ）

`Authorization`プラグインや`AuthComponent`を使用している場合、`AutoIssuerComponent`がテーブルに実行者を自動設定してくれます：

```php
// AppControllerにて
class AppController extends Controller
{
    public function initialize(): void
    {
        // ...
        $this->loadComponent('Elastic/ActivityLogger.AutoIssuer', [
            'userModel' => 'Users',  // ユーザーモデル名を指定
        ]);
        // ...
    }
}
```

### スコープ付きアクティビティログ

複数のモデルに関連する操作のログを記録できます：

```php
class CommentsTable extends Table
{
    public function initialize(array $config): void
    {
        // ...

        $this->addBehavior('Elastic/ActivityLogger.Logger', [
            'scope' => [
                'Articles',  // 記事
                'Authors',   // 著者
                'Users',     // ユーザー
            ],
        ]);
    }
}
```

```php
$this->Comments->setLogScope([$user, $article]); // スコープを設定

$comment = $this->Comments->newEntity([ /* データ */ ]);
$this->Comments->save($comment);

// 保存されるログ
// [action='create', scope_model='Users', scope_id=$user->id, ...]
// および
// [action='create', scope_model='Articles', scope_id=$article->id, ...]
```

### メッセージ付きアクティビティログ

`setLogMessageBuilder`メソッドを使用して、ログのアクションごとにカスタムメッセージを生成できます：

```php
class ArticlesTable extends Table
{
    public function initialize(array $config): void
    {
        // ...

        $this->addBehavior('Elastic/ActivityLogger.Logger', [
            'scope' => [
                'Articles',
                'Authors',
            ],
        ]);

        // メッセージビルダーを追加
        $this->setLogMessageBuilder(static function (ActivityLog $log, array $context) {
            if ($log->message !== null) {
               return $log->message;
            }
            
            $message = '';
            $object = $context['object'] ?: null;
            $issuer = $context['issuer'] ?: null;
            switch ($log->action) {
                case ActivityLog::ACTION_CREATE:
                    $message = sprintf('%3$s が記事 #%1$s: "%2$s" を作成しました', $object->id, $object->title, $issuer->username);
                    break;
                case ActivityLog::ACTION_UPDATE:
                    $message = sprintf('%3$s が記事 #%1$s: "%2$s" を更新しました', $object->id, $object->title, $issuer->username);
                    break;
                case ActivityLog::ACTION_DELETE:
                    $message = sprintf('%3$s が記事 #%1$s: "%2$s" を削除しました', $object->id, $object->title, $issuer->username);
                    break;
                default:
                    break;
            }
            
            return $message;
        });
    }
}
```

または、保存・削除処理の前に`setLogMessage`を使用してログメッセージを設定することもできます：

```php
$this->Articles->setLogMessage('カスタムメッセージ');
$this->Articles->save($entity);
// 保存されるログ
// [action='update', 'message' => 'カスタムメッセージ', ...]
```

### カスタムログの保存

独自のアクティビティログを記録することも可能です：

```php
$this->Articles->activityLog(\Psr\Log\LogLevel::NOTICE, 'カスタムメッセージ', [
  'action' => 'custom',
  'object' => $article,
]);

// 保存されるログ
// [action='custom', 'message' => 'カスタムメッセージ', scope_model='Articles', scope_id=$article->id, ...]
```

### アクティビティログの検索

記録されたアクティビティログを検索できます：

```php
$logs = $this->Articles->find('activity', ['scope' => $article]);
```

## 高度な使用例

### 条件付きログ記録

特定の条件でのみログを記録したい場合：

```php
// 特定のフィールドが変更された場合のみログを記録
if ($article->isDirty('status')) {
    $this->Articles->setLogMessage('ステータスが変更されました');
}
$this->Articles->save($article);
```

### バッチ処理でのログ記録

大量データの処理時には、ログ記録を一時的に無効化できます：

```php
// ログ記録を一時的に無効化
$behavior = $this->Authors->disableActivityLog();

// バッチ処理
foreach ($articles as $article) {
    $this->Articles->save($article);
}

// ログ記録を再有効化
$this->Articles->enableActivityLog();
```

## トラブルシューティング

### よくある問題

**Q: ログが記録されません**

A: 以下を確認してください：
- マイグレーションが実行されているか
- Behaviorが正しくアタッチされているか
- データベース接続に問題がないか

**Q: 実行者の情報が記録されません**

A: 以下を確認してください：
- AutoIssuerMiddleware使用時：認証ミドルウェアの後に配置されているか確認
- AutoIssuerComponent使用時：コントローラーのinitialize()メソッドで読み込まれているか確認
- 必要に応じて`setLogIssuer()`で手動設定されているか確認
- ユーザーモデルの設定がアプリケーションのユーザーテーブルと一致しているか確認

**Q: パフォーマンスに影響がありますか？**

A: 
- AutoIssuerMiddlewareはリクエストごとに一度処理されるため、パフォーマンスへの影響は最小限です
- 大量のデータを扱う際は、必要に応じてログ記録を一時的に無効化することを検討してください

**Q: 動的に読み込まれたテーブルに実行者が設定されません**

A: AutoIssuerMiddlewareは`Model.initialize`イベントにフックします。以下を確認してください：
- テーブルアクセスの前にミドルウェアが読み込まれている
- テーブルがTableLocatorを通じて読み込まれている（手動でインスタンス化していない）
- LoggerBehaviorがテーブルにアタッチされている

## ライセンス

MITライセンス。詳細は[LICENSE.txt](LICENSE.txt)を参照してください。

## 貢献

バグレポートや機能要求は[GitHub Issues](https://github.com/elstc/cakephp-activity-logger/issues)にお寄せください。

プルリクエストも歓迎します。大きな変更を行う前に、まずIssueで議論することをお勧めします。
