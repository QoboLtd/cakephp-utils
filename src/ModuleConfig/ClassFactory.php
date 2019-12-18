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
namespace Qobo\Utils\ModuleConfig;

use Cake\Core\Configure;
use InvalidArgumentException;
use Qobo\Utils\ModuleConfig\Parser\ParserInterface;
use Qobo\Utils\ModuleConfig\PathFinder\PathFinderInterface;
use ReflectionClass;
use Webmozart\Assert\Assert;

/**
 * ClassFactory Class
 *
 * This class provides a factory design
 * pattern for instantiating helper
 * classes like path finders and parsers.
 */
class ClassFactory
{
    /**
     * Create a new instance of a helper class
     *
     * Options:
     * - classArgs: array of class arguments which will be passed to the constructor.
     *
     * @throws \InvalidArgumentException when cannot create instance
     * @param string $configType Configuration type
     * @param \Qobo\Utils\ModuleConfig\ClassType $classType Class type
     * @param mixed[] $options Options
     * @return object
     */
    public static function create(string $configType, ClassType $classType, array $options = [])
    {
        $classType = (string)$classType;

        $classMapVersion = empty($options['classMapVersion']) ? Configure::read('ModuleConfig.classMapVersion') : (string)$options['classMapVersion'];
        $classMap = empty($options['classMap'][$classMapVersion]) ? Configure::read('ModuleConfig.classMap.' . $classMapVersion) : (array)$options['classMap'][$classMapVersion];
        if (empty($classMap[$configType][$classType])) {
            throw new InvalidArgumentException("No [$classType] found for configuration type [$configType] in class map version [$classMapVersion]");
        }

        $className = $classMap[$configType][$classType];
        $params = $options['classArgs'] ?? [];
        $result = static::getInstance($className, $params);

        return $result;
    }

    /**
     * Create a new instance of a Parser class
     *
     * Options:
     * - classArgs: array of class arguments which will be passed to the constructor.
     *
     * @param string $configType Configuration type
     * @param mixed[] $options Options
     * @return \Qobo\Utils\ModuleConfig\Parser\ParserInterface
     */
    public static function createParser(string $configType, array $options = []): ParserInterface
    {
        $parser = self::create($configType, ClassType::PARSER(), $options);
        Assert::isInstanceOf($parser, ParserInterface::class);

        return $parser;
    }

    /**
     * Create a new instance of a Parser class
     *
     * Options:
     * - classArgs: array of class arguments which will be passed to the constructor.
     *
     * @param string $configType Configuration type
     * @param mixed[] $options Options
     * @return \Qobo\Utils\ModuleConfig\PathFinder\PathFinderInterface
     */
    public static function createFinder(string $configType, array $options = []): PathFinderInterface
    {
        $parser = self::create($configType, ClassType::FINDER(), $options);
        Assert::isInstanceOf($parser, PathFinderInterface::class);

        return $parser;
    }

    /**
     * Get an instance of a given class
     *
     * This method is public because it is useful in a variety of
     * situations, not just for the factory via class map.
     *
     * @throws \InvalidArgumentException when cannot create instance
     * @param string $class Class name to instantiate
     * @param mixed[] $params Optional parameters to the class
     * @return object
     */
    public static function getInstance(string $class, array $params = [])
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class [$class] does not exist");
        }

        if (empty($params)) {
            return new $class();
        }

        $reflection = new ReflectionClass($class);

        return $reflection->newInstanceArgs($params);
    }
}
