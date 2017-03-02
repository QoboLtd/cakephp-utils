[![codecov](https://codecov.io/gh/QoboLtd/cakephp-utils/branch/master/graph/badge.svg)](https://codecov.io/gh/QoboLtd/cakephp-utils) 
[![Build Status](https://travis-ci.org/QoboLtd/cakephp-utils.svg?branch=master)](https://travis-ci.org/QoboLtd/cakephp-utils)
[![Latest Stable Version](https://poser.pugx.org/qobo/cakephp-utils/v/stable)](https://packagist.org/packages/qobo/cakephp-csv-migrations)
[![Total Downloads](https://poser.pugx.org/qobo/cakephp-utils/downloads)](https://packagist.org/packages/qobo/cakephp-csv-migrations)
[![Latest Unstable Version](https://poser.pugx.org/qobo/cakephp-utils/v/unstable)](https://packagist.org/packages/qobo/cakephp-csv-migrations)
[![License](https://poser.pugx.org/qobo/cakephp-utils/license)](https://packagist.org/packages/qobo/cakephp-csv-migrations)
CakePHP Plugin Template
=======================

Template for CakePHP 3 plugins.

Usage
-----

Pull the template code into your plugin:

```
cd my-plugin
git pull https://github.com/QoboLtd/cakephp-plugin-template master
```

Make sure your `composer.json` has something like this:

```
"autoload": {
    "psr-4": {
        "Foobar\\": "src"
    }
},
"autoload-dev": {
    "psr-4": {
        "Foobar\\Test\\": "tests",
        "Cake\\Test\\": "./vendor/cakephp/cakephp/tests"
    }
}
```

If you do change your `composer.json` file, don't forget to run
either `composer update` or at least `composer dump-autoload`.

Change the following:

1. Uncomment the `$pluginName` line in `tests/bootstrap.php` and change `Foobar` to the name of your plugin.
2. Change the `Foobar` namespace to your plugin's in the following files:
  1. `tests/config/routes.php`
  2. `tests/App/Controller/AppController.php`
  3. `tests/App/Controller/UsersController.php`
  4. `tests/Fixture/UsersFixture.php`
3. Put your tests into `tests/TestCase`.
4. Put your fixtures into `tests/Fixture`.
5. Run `vendor/bin/phpunit`

If you know of any better way to do this please open an issue on GitHub or, better even, send a pull request.
