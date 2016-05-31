# ActivityLogger plugin for CakePHP 3.x

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

fisrt, add repositories section to `composer.json`

```
"repositories": [
    {
        "type": "git",
        "url": "ssh://git@git.elasticconsultants.info:20022/elastic/cakephp-activity-logger.git"
    }
]
```

### run `composer require`

```
composer require elastic/cakephp-activity-logger
```

### Load plugin bootstrap

in `config/bootstrap.php`

```(php)
use Cake\Core\Plugin;
Plugin::load('Elastic/ActivityLogger', ['bootstrap' => true]);
```

### Create activity_logs table

run migration command:

```
bin/cake migrations migrate -p Elastic/ActivityLogger
```


## Usage

### Attach to Table

```(php)
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
```(php)
$artice = $this-Articles->newEnity([ /* ... */ ]);
$this->Articles->save($artice);
// saved log
// [action='create', scope_model='Articles', scope_id=$article->id]
```

#### logging on update
```(php)
$artice = $this-Articles->patchEnity(artice, [ /* ... */ ]);
$this->Articles->save($artice);
// saved log
// [action='update', scope_model='Articles', scope_id=$article->id]
```

#### logging on delete
```(php)
$artice = $this-Articles->get($id);
$this->Articles->delete($artice);
// saved log
// [action='delete', scope_model='Articles', scope_id=$article->id]
```

### Activity Logging with Issuer

```(php)
$this->Articles->logIssuer($author); // Set issuer

$artice = $this-Articles->newEnity([ /* ... */ ]);
$this->Articles->save($artice);

// saved log
// [action='create', scope_model='Articles', scope_id=$article->id, ...]
// and
// [action='create', scope_model='Auhtors', scope_id=$author->id, ...]
```

### Activity Logging with Scope

```(php)
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

```(php)
$this->Comments->logScope([$user, $article]); // Set scope

$comment = $this-Comments->newEnity([ /* ... */ ]);
$this->Comments->save($comment);

// saved log
// [action='create', scope_model='Users', scope_id=$article->id, ...]
// and
// [action='create', scope_model='Articles', scope_id=$author->id, ...]
```

### Save Custom Log

```(php)
$this->Articles->activityLog(\Psr\Log\LogLevel::NOTICE, 'Custom Messages', [
  'action' => 'custom',
  'object' => $artice,
]);

// saved log
// [action='custom', 'message' => 'Custom Messages', scope_model='Articles', scope_id=$article->id, ...]
```

### Find Activity Logs

```(php)
$logs = $this->Articles->find('activity', ['scope' => $article]);
```
