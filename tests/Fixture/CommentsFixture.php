<?php

namespace Elastic\ActivityLogger\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class CommentsFixture extends TestFixture {

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'article_id' => ['type' => 'integer', 'null' => false],
        'user_id' => ['type' => 'integer', 'null' => false],
        'comment' => ['type' => 'text'],
        'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
        'created' => ['type' => 'datetime'],
        'updated' => ['type' => 'datetime'],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'],
        ['article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'],
        ['article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'],
        ['article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'],
        ['article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'],
        ['article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31']
    ];

}
