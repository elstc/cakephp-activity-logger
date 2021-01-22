# ActivityLogger plugin for CakePHP 4.x

<p style="text-align: center">
    <a href="LICENSE.txt" target="_blank">
        <img alt="Software License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square">
    </a>
    <a href="https://github.com/nojimage/cakephp-activity-logger/actions" target="_blank">
        <img alt="Build Status" src="https://img.shields.io/github/workflow/status/nojimage/cakephp-activity-logger/CakePHP%20Plugin%20CI/cake4?style=flat-square">
    </a>
    <a href="https://codecov.io/gh/elstc/cakephp-activity-logger" target="_blank">
        <img alt="Codecov" src="https://img.shields.io/codecov/c/github/elstc/cakephp-activity-logger.svg?style=flat-square">
    </a>
    <a href="https://packagist.org/packages/elstc/cakephp-activity-logger" target="_blank">
        <img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/elstc/cakephp-activity-logger.svg?style=flat-square">
    </a>
</p>

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require elstc/cakephp-activity-logger
```

### Load plugin

Load the plugin by adding the following statement in your project's `src/Application.php`:

```
$this->addPlugin('Elastic/ActivityLogger');
```

### Create activity_logs table

run migration command:

```
bin/cake migrations migrate -p Elastic/ActivityLogger
```


## Usage

### Attach to Table

```php
class ArticlesTable extends Table
{

    public function initialize(array $config)
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

### Activity Logging Basis

#### logging on create
```php
$artice = $this-Articles->newEnity([ /* ... */ ]);
$this->Articles->save($artice);
// saved log
// [action='create', scope_model='Articles', scope_id=$article->id]
```

#### logging on update
```php
$artice = $this-Articles->patchEnity(artice, [ /* ... */ ]);
$this->Articles->save($artice);
// saved log
// [action='update', scope_model='Articles', scope_id=$article->id]
```

#### logging on delete
```php
$artice = $this-Articles->get($id);
$this->Articles->delete($artice);
// saved log
// [action='delete', scope_model='Articles', scope_id=$article->id]
```

### Activity Logging with Issuer

```php
$this->Articles->setLogIssuer($author); // Set issuer

$artice = $this-Articles->newEnity([ /* ... */ ]);
$this->Articles->save($artice);

// saved log
// [action='create', scope_model='Articles', scope_id=$article->id, ...]
// and
// [action='create', scope_model='Auhtors', scope_id=$author->id, ...]
```

#### AutoIssuerComponent

If you using `Authorization` plugin or `AuthComponent`, the `AutoIssuerComponent` will help set issuer to Tables.

```php
// In AppController
class AppController extends Controller
{
    public function initialize()
    {
        // ...
        $this->loadComponent('Elastic/ActivityLogger.AutoIssuer', [
            'userModel' => 'Users',
        ]);
        // ...
    }
}
```

If there is load to any Table class before the execution of `Controller.startup` event,
please describe `initializedTables` option.

eg: 

```php
// In AppController
class AppController extends Controller
{
    public function initialize()
    {
        $this->loadModel('Articles');
        $this->loadModel('Awesome.Favorites');

        // ...

        $this->loadComponent('Elastic/ActivityLogger.AutoIssuer', [
            'userModel' => 'Users',
            'initializedTables' => [
                'Articles',
                'Awesome.Favorites',
            ],
        ]);

        // ...
    }
}
```

### Activity Logging with Scope

```php
class CommentsTable extends Table
{

    public function initialize(array $config)
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

$comment = $this-Comments->newEnity([ /* ... */ ]);
$this->Comments->save($comment);

// saved log
// [action='create', scope_model='Users', scope_id=$article->id, ...]
// and
// [action='create', scope_model='Articles', scope_id=$author->id, ...]
```

### Activity Logging with message

use `setLogMessageBuilder` method. You can generate any message for each action in the log.

```php
class ArticlesTable extends Table
{

    public function initialize(array $config)
    {
        // ...

        $this->addBehavior('Elastic/ActivityLogger.Logger', [
            'scope' => [
                'Articles',
                'Authors',
            ],
        ]);
        // ADD THIS
        $this->setLogMessageBuilder(static function (ActivityLog $log, array $context) {
            if ($log->message !== null) {
               return $log->message;
            }
            
            $message = '';
            $object = $context['object'] ?: null;
            $issuer = $context['issuer'] ?: null;
            switch ($log->action) {
                case ActivityLog::ACTION_CREATE:
                    $message = sprintf('%3$s created #%1$s: "%2$s"', $object->id, $object->title, $issuer->username);
                    break;
                case ActivityLog::ACTION_UPDATE:
                    $message = sprintf('%3$s updated #%1$s: "%2$s"', $object->id, $object->title, $issuer->username);
                    break;
                case ActivityLog::ACTION_DELETE:
                    $message = sprintf('%3$s deleted #%1$s: "%2$s"', $object->id, $object->title, $issuer->username);
                    break;
                default:
                    break;
            }
            
            return $message;
        });
    }
}

```

Or use `setLogMessage` before save|delete action. You can set a log message. 

```php
$this->Articles->setLogMessage('Custom Message');
$this->Articles->save($entity);
// saved log
// [action='update', 'message' => 'Custom Messages', ...]
```

### Save Custom Log

```php
$this->Articles->activityLog(\Psr\Log\LogLevel::NOTICE, 'Custom Messages', [
  'action' => 'custom',
  'object' => $artice,
]);

// saved log
// [action='custom', 'message' => 'Custom Messages', scope_model='Articles', scope_id=$article->id, ...]
```

### Find Activity Logs

```php
$logs = $this->Articles->find('activity', ['scope' => $article]);
```
