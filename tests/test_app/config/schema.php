<?php
declare(strict_types=1);

/**
 * Abstract schema for CakePHP tests.
 *
 * This format resembles the existing fixture schema
 * and is converted to SQL via the Schema generation
 * features of the Database package.
 */
return [
    'users' => [
        'table' => 'users',
        'columns' => [
            'id' => ['type' => 'integer'],
            'username' => ['type' => 'string', 'null' => true],
            'password' => ['type' => 'string', 'null' => true],
            'created' => ['type' => 'timestamp', 'null' => true],
            'updated' => ['type' => 'timestamp', 'null' => true],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'articles' => [
        'table' => 'articles',
        'columns' => [
            'id' => ['type' => 'integer'],
            'author_id' => ['type' => 'integer', 'null' => true],
            'title' => ['type' => 'string', 'null' => true, 'collation' => 'utf8mb4_general_ci'],
            'body' => ['type' => 'text', 'null' => true, 'collation' => 'utf8mb4_general_ci'],
            'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'comments' => [
        'table' => 'comments',
        'columns' => [
            'id' => ['type' => 'integer'],
            'article_id' => ['type' => 'integer', 'null' => false],
            'user_id' => ['type' => 'integer', 'null' => false],
            'comment' => ['type' => 'text', 'collation' => 'utf8mb4_general_ci'],
            'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
            'created' => ['type' => 'datetime'],
            'updated' => ['type' => 'datetime'],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'authors' => [
        'table' => 'authors',
        'columns' => [
            'id' => ['type' => 'integer'],
            'username' => ['type' => 'string', 'null' => true],
            'password' => ['type' => 'string', 'null' => true],
            'created' => ['type' => 'timestamp', 'null' => true],
            'updated' => ['type' => 'timestamp', 'null' => true],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
];
