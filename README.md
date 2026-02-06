# ActivityLogger plugin for CakePHP 5.x

ActivityLogger plugin automatically logs database operations (create, update, delete) in CakePHP applications. It tracks who, when, and what was changed.

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

## Requirements

- PHP 8.1 or higher
- CakePHP 5.0 or higher
- PDO extension
- JSON extension

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require elstc/cakephp-activity-logger:^3.0
```

### Load plugin

Load the plugin by adding the following statement in your project's `src/Application.php`:

```php
$this->addPlugin('Elastic/ActivityLogger');
```

### Create the activity_logs table

Run migration command:

```
bin/cake migrations migrate -p Elastic/ActivityLogger
```

## Usage

### Attach to Table

Attach the ActivityLogger plugin to your table to enable automatic logging:

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

### Basic Activity Logging

#### Logging on create
```php
$article = $this->Articles->newEntity([ /* data */ ]);
$this->Articles->save($article);
// saved log
// [action='create', scope_model='Articles', scope_id=$article->id]
```

#### Logging on update
```php
$article = $this->Articles->patchEntity($article, [ /* update data */ ]);
$this->Articles->save($article);
// saved log
// [action='update', scope_model='Articles', scope_id=$article->id]
```

#### Logging on delete
```php
$article = $this->Articles->get($id);
$this->Articles->delete($article);
// saved log
// [action='delete', scope_model='Articles', scope_id=$article->id]
```

### Activity Logging with Issuer

You can log information about the user who performed the operation:

```php
$this->Articles->setLogIssuer($author); // Set issuer

$article = $this->Articles->newEntity([ /* data */ ]);
$this->Articles->save($article);

// saved log
// [action='create', scope_model='Articles', scope_id=$article->id, ...]
// and
// [action='create', scope_model='Authors', scope_id=$author->id, ...]
```

#### AutoIssuerMiddleware (Recommended for CakePHP 4.x+)

`AutoIssuerMiddleware` is a PSR-15 compliant middleware that provides automatic issuer setting for applications using the `Authorization` plugin.
This middleware operates at the application level and processes authentication information early in the request lifecycle.

##### Installation and Configuration

```php
// In src/Application.php
use Elastic\ActivityLogger\Http\Middleware\AutoIssuerMiddleware;

class Application extends BaseApplication
{
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // ... other middleware
            ->add(new AuthenticationMiddleware($this))
            
            // Add AutoIssuerMiddleware AFTER authentication middleware
            ->add(new AutoIssuerMiddleware([
                'userModel' => 'Users',           // User model name (default: 'Users')
                'identityAttribute' => 'identity', // Request attribute name (default: 'identity')
            ]))
            
            // ... other middleware
            ->add(new RoutingMiddleware($this));
            
        return $middlewareQueue;
    }
}
```

##### Important Notes

- **Middleware Order**: Always place AutoIssuerMiddleware AFTER authentication middleware

#### AutoIssuerComponent (Legacy Approach)

If you're using `Authorization` plugin or `AuthComponent`, the `AutoIssuerComponent` will automatically set the issuer to Tables:

```php
// In AppController
class AppController extends Controller
{
    public function initialize(): void
    {
        // ...
        $this->loadComponent('Elastic/ActivityLogger.AutoIssuer', [
            'userModel' => 'Users',  // Specify user model name
        ]);
        // ...
    }
}
```

### Activity Logging with Scope

You can log operations related to multiple models:

```php
class CommentsTable extends Table
{
    public function initialize(array $config): void
    {
        // ...

        $this->addBehavior('Elastic/ActivityLogger.Logger', [
            'scope' => [
                'Articles',
                'Authors',
                'Users',
            ],
        ]);
    }
}
```

```php
$this->Comments->setLogScope([$user, $article]); // Set scope

$comment = $this->Comments->newEntity([ /* data */ ]);
$this->Comments->save($comment);

// saved log
// [action='create', scope_model='Users', scope_id=$user->id, ...]
// and
// [action='create', scope_model='Articles', scope_id=$article->id, ...]
```

### Activity Logging with Custom Messages

You can use the `setLogMessageBuilder` method to generate custom messages for each log action:

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

        // Add message builder
        $this->setLogMessageBuilder(static function (ActivityLog $log, array $context) {
            if ($log->message !== null) {
               return $log->message;
            }
            
            $message = '';
            $object = $context['object'] ?: null;
            $issuer = $context['issuer'] ?: null;
            switch ($log->action) {
                case ActivityLog::ACTION_CREATE:
                    $message = sprintf('%3$s created article #%1$s: "%2$s"', $object->id, $object->title, $issuer->username);
                    break;
                case ActivityLog::ACTION_UPDATE:
                    $message = sprintf('%3$s updated article #%1$s: "%2$s"', $object->id, $object->title, $issuer->username);
                    break;
                case ActivityLog::ACTION_DELETE:
                    $message = sprintf('%3$s deleted article #%1$s: "%2$s"', $object->id, $object->title, $issuer->username);
                    break;
                default:
                    break;
            }
            
            return $message;
        });
    }
}
```

Alternatively, you can use `setLogMessage` before save/delete operations to set a log message:

```php
$this->Articles->setLogMessage('Custom Message');
$this->Articles->save($entity);
// saved log
// [action='update', 'message' => 'Custom Message', ...]
```

### Save Custom Log

You can also record your own activity logs:

```php
$this->Articles->activityLog(\Psr\Log\LogLevel::NOTICE, 'Custom Message', [
  'action' => 'custom',
  'object' => $article,
]);

// saved log
// [action='custom', 'message' => 'Custom Message', scope_model='Articles', scope_id=$article->id, ...]
```

### Find Activity Logs

You can search recorded activity logs:

```php
$logs = $this->Articles->find('activity', ['scope' => $article]);
```

## Advanced Usage Examples

### Conditional Logging

When you want to log only under certain conditions:

```php
// Log only when specific fields are changed
if ($article->isDirty('status')) {
    $this->Articles->setLogMessage('Status was changed');
}
$this->Articles->save($article);
```

### Batch Processing with Logging

During large data processing, you can temporarily disable logging:

```php
// Temporarily disable logging
$behavior = $this->Authors->disableActivityLog();

// Batch processing
foreach ($articles as $article) {
    $this->Articles->save($article);
}

// Re-enable logging
$this->Articles->enableActivityLog();
```

## Upgrading

### CakePHP 5.3+

CakePHP 5.3 deprecated calling behavior methods directly on the Table class. To maintain backward compatibility with your existing code, add `LoggerTrait` to your Table classes:

```php
use Elastic\ActivityLogger\Model\Table\LoggerTrait;

class ArticlesTable extends Table
{
    use LoggerTrait;

    public function initialize(array $config): void
    {
        $this->addBehavior('Elastic/ActivityLogger.Logger');
    }
}
```

With this trait, you can continue using the familiar method calls:

```php
// These methods work as before
$this->Articles->setLogScope($article);
$this->Articles->setLogIssuer($user);
$this->Articles->setLogMessage('Custom message');
```

Without the trait, you need to call methods through the behavior:

```php
// Without LoggerTrait (CakePHP 5.3+ recommended pattern)
$this->Articles->getBehavior('Logger')->setLogScope($article);
```

## Troubleshooting

### Common Issues

**Q: Logs are not being recorded**

A: Please check the following:
- Whether migrations have been executed
- Whether the Behavior is properly attached
- Whether there are any database connection issues

**Q: Issuer information is not being recorded**

A: Please check the following:
- If using AutoIssuerMiddleware: Ensure it's placed AFTER authentication middleware in the middleware queue
- If using AutoIssuerComponent: Verify it's loaded in your controller's initialize() method
- Check if `setLogIssuer()` is manually set when needed
- Verify the user model configuration matches your application's user table

**Q: AutoIssuerMiddleware vs AutoIssuerComponent - Which should I use?**

A: 
- **Use AutoIssuerMiddleware** (Recommended for CakePHP 4.x+):
  - For new applications
  - When you need application-wide issuer tracking
  - For better performance and cleaner architecture
  - When using PSR-15 middleware stack
  
- **Use AutoIssuerComponent**:
  - For legacy applications or CakePHP 3.x
  - When you need controller-specific issuer handling
  - For backward compatibility

**Q: Is there any performance impact?**

A: 
- AutoIssuerMiddleware has minimal performance impact as it processes once per request
- When handling large amounts of data, consider temporarily disabling logging as needed

**Q: The issuer is not set for dynamically loaded Tables**

A: The AutoIssuerMiddleware hooks into `Model.initialize` events. Ensure:
- The middleware is loaded before any Table access
- Tables are loaded through the TableLocator (not manually instantiated)
- The LoggerBehavior is attached to the Table

## License

MIT License. See [LICENSE.txt](LICENSE.txt) for details.

## Contributing

Bug reports and feature requests are welcome at [GitHub Issues](https://github.com/elstc/cakephp-activity-logger/issues).

Pull requests are also welcome. We recommend discussing large changes in an Issue first.
