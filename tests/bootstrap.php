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

// Disable deprecations for now when using 3.6
if (version_compare(Configure::version(), '3.6.0', '>=')) {
    error_reporting(E_ALL ^ E_USER_DEPRECATED);
}
if (class_exists('\Cake\I18n\FrozenTime')) {
    \Cake\I18n\FrozenTime::setJsonEncodeFormat('yyyy-MM-dd\'T\'HH:mm:ssxxx');
}
\Cake\I18n\Time::setJsonEncodeFormat('yyyy-MM-dd\'T\'HH:mm:ssxxx');

Plugin::load('Elastic/ActivityLogger', ['path' => dirname(dirname(__FILE__)) . DS, 'bootstrap' => true]);

error_reporting(E_ALL);

require $here . '/test_models.php';
