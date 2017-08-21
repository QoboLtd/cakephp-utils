<?php
// @codingStandardsIgnoreFile

use Cake\Core\Configure;
use Cake\Filesystem\Folder;

$pluginName = 'Utils';
if (empty($pluginName)) {
    throw new \Exception("Plugin name is not configured");
}

$findRoot = function () {
    $root = dirname(__DIR__);
    if (is_dir($root . '/vendor/cakephp/cakephp')) {
        return $root;
    }

    $root = dirname(dirname(__DIR__));
    if (is_dir($root . '/vendor/cakephp/cakephp')) {
        return $root;
    }

    $root = dirname(dirname(dirname(__DIR__)));
    if (is_dir($root . '/vendor/cakephp/cakephp')) {
        return $root;
    }

    throw new \Exception("Failed to find CakePHP");
};

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', $findRoot());
define('APP_DIR', 'App');
define('WEBROOT_DIR', 'webroot');
define('APP', ROOT . '/tests/App/');
define('CONFIG', ROOT . '/tests/config/');
define('WWW_ROOT', ROOT . DS . WEBROOT_DIR . DS);
define('TESTS', ROOT . DS . 'tests' . DS);
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);

require ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';

Configure::write('App', [
    'namespace' => 'Qobo\\' . $pluginName . '\Test\App',
    'paths' => [
        'templates' => [
            APP . 'Template' . DS
        ]
    ]
]);
Configure::write('debug', true);

$TMP = new Folder(TMP);
$TMP->create(TMP . 'cache/models', 0777);
$TMP->create(TMP . 'cache/persistent', 0777);
$TMP->create(TMP . 'cache/views', 0777);

$cache = [
    'default' => [
        'engine' => 'File'
    ],
    '_cake_core_' => [
        'className' => 'File',
        'prefix' => strtolower($pluginName) . '_myapp_cake_core_',
        'path' => CACHE . 'persistent/',
        'serialize' => true,
        'duration' => '+10 seconds'
    ],
    '_cake_model_' => [
        'className' => 'File',
        'prefix' => strtolower($pluginName) . '_my_app_cake_model_',
        'path' => CACHE . 'models/',
        'serialize' => 'File',
        'duration' => '+10 seconds'
    ]
];

Cake\Cache\Cache::config($cache);
Cake\Core\Configure::write('Session', [
    'defaults' => 'php'
]);

// Ensure default test connection is defined
if (!getenv('db_dsn')) {
    putenv('db_dsn=sqlite:///:memory:');
}

Cake\Datasource\ConnectionManager::config('default', [
    'url' => getenv('db_dsn'),
    'quoteIdentifiers' => true,
    'timezone' => 'UTC'
]);

Cake\Datasource\ConnectionManager::config('test', [
    'url' => getenv('db_dsn'),
    'quoteIdentifiers' => true,
    'timezone' => 'UTC'
]);

// loading test icons.php from test/config/icons.php
Cake\Core\Configure::load('icons', 'default');
Cake\Core\Configure::load('colors', 'default');
Cake\Core\Configure::load('module_config', 'default');

// Alias AppController to the test App
class_alias('Qobo\\' . $pluginName . '\Test\App\Controller\AppController', 'App\Controller\AppController');
// If plugin has routes.php/bootstrap.php then load them, otherwise don't.
$loadPluginRoutes = file_exists(dirname(__FILE__) . DS . 'config' . DS . 'routes.php');
$loadPluginBootstrap = file_exists(dirname(__FILE__) . DS . 'config' . DS . 'bootstrap.php');
Cake\Core\Plugin::load('Qobo/' . $pluginName, ['path' => ROOT . DS, 'autoload' => true, 'routes' => $loadPluginRoutes, 'bootstrap' => $loadPluginBootstrap]);

Cake\Routing\DispatcherFactory::add('Routing');
Cake\Routing\DispatcherFactory::add('ControllerFactory');
