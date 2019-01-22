<?php

use Cake\Core\Configure;
use Cake\Core\Plugin;

/**
 * Test suite bootstrap for RememberMe.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */
$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception("Cannot find the root of the application, unable to run tests");
};
$root = $findRoot(__FILE__);
unset($findRoot);

$here = __DIR__;

chdir($root);
require $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';
Configure::write('Error.errorLevel', E_ALL & ~E_USER_DEPRECATED);

Plugin::load('Elastic/ActivityLogger', ['path' => dirname(dirname(__FILE__)) . DS, 'bootstrap' => true]);

require $here . '/test_models.php';
