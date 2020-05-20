<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Qobo\Utils\Module;

use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

class ModuleRegistry extends ObjectRegistry
{
    /**
     * Singleton instance.
     * @var \Qobo\Utils\Module\ModuleRegistry|null
     */
    private static $instance;

    /**
     * Private constructor.
     */
    private function __construct()
    {
    }

    /**
     * Protect from cloning.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Protect from serialization.
     *
     * @return void
     */
    private function __wakeup()
    {
    }

    /**
     * Returns a singleton instance of ModuleRegistry.
     *
     * @return \Qobo\Utils\Module\ModuleRegistry
     */
    public static function instance(): ModuleRegistry
    {
        if (!(static::$instance instanceof self)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Returns an instance of a given module.
     *
     * @param string $moduleName Name of module.
     * @param mixed[] $config Additional settings to use when loading the object.
     * @return \Qobo\Utils\Module\ModuleInterface
     */
    public static function getModule(string $moduleName, array $config = []): ModuleInterface
    {
        $module = self::instance()->get($moduleName);
        if ($module === null) {
            $module = self::instance()->load($moduleName, $config);
        }
        Assert::isInstanceOf($module, ModuleInterface::class, (string)__d('Qobo/Utils', 'Module `{0}` is not an instance of `{1}`', $moduleName, ModuleInterface::class));

        return $module;
    }

    /**
     * {@inheritDoc}
     */
    protected function _create($class, $alias, $config)
    {
        $instance = new $class();

        return $instance;
    }

    /**
     * Resolve a state machine class name.
     *
     * @param string $class Partial classname to resolve.
     * @return string|null Either the correct classname or null.
     */
    public static function className(string $class): ?string
    {
        $result = App::className($class, 'Module', 'Module');

        return $result ?: null;
    }

    /**
     * {@inheritDoc}
     */
    protected function _resolveClassName($class)
    {
        return static::className($class) ?: false;
    }

    /**
     * {@inheritDoc}
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        throw new InvalidArgumentException((string)__d('Qobo/Utils', 'Module `{0}` does not exist.', $class));
    }
}
