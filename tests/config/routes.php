<?php
namespace Qobo\Utils\Test\App\Config;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Routing\Router;

/**
 * Load all plugin routes.  See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
Plugin::routes();

Router::connect('/users/login', ['controller' => 'Users', 'action' => 'login']);
