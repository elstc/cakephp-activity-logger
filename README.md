# ActivityLogger plugin for CakePHP 3.x

<p align="center">
    <a href="LICENSE.txt" target="_blank">
        <img alt="Software License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square">
    </a>
    <a href="https://travis-ci.org/elstc/cakephp-activity-logger" target="_blank">
        <img alt="Build Status" src="https://img.shields.io/travis/elstc/cakephp-activity-logger/master.svg?style=flat-square">
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

(CakePHP >= 3.6.0) Load the plugin by adding the following statement in your project's `src/Application.php`:

```
$this->addPlugin('Elastic/ActivityLogger');
```

(CakePHP <= 3.5.x) Load the plugin by adding the following statement in your project's `config/bootstrap.php` file:

```
Plugin::load('Elastic/ActivityLogger', ['bootstrap' => true]);
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

If you using `AuthComponent`, the `AutoIssuerComponent` will help set issuer to Tables.

```php
// In AppController
class AppController extends Controller
{
    public function initialize()
    {
        // ...
        $this->loadComonent('Elastic/ActivityLogger.AutoIssuer', [
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

        $this->loadComonent('Elastic/ActivityLogger.AutoIssuer', [
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
