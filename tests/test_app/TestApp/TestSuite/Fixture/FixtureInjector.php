<?php
declare(strict_types=1);

namespace TestApp\TestSuite\Fixture;

use PHPUnit\Framework\TestSuite;

class FixtureInjector extends \Cake\TestSuite\Fixture\FixtureInjector
{
    /**
     * @inheritDoc
     */
    public function startTestSuite(TestSuite $suite): void
    {
        // deprecated CakePHP <= 4.3.0
        error_reporting(E_ALL & ~E_USER_DEPRECATED);
        parent::startTestSuite($suite);
        error_reporting(E_ALL);
    }
}
