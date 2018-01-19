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

use Exception;
use Qobo\Utils\ErrorAwareInterface;
use Qobo\Utils\ErrorTrait;
use Qobo\Utils\ModuleConfig\Cache\Cache;
use Qobo\Utils\ModuleConfig\Cache\PathCache;
use stdClass;

/**
 * ModuleConfig Class
 *
 * This class assists with finding, parsing and validating a
 * variety of module configurations available in CsvMigrations
 * CakePHP plugin.  Some of the things that it understands are:
 *
 * * migration.csv
 * * config.ini
 * * menus.json
 * * fields.ini
 * * reports.ini
 * * lists CSV files
 * * views CSV files
 *
 * @author Leonid Mamchenkov <l.mamchenkov@qobo.biz>
 */
class ModuleConfig implements ErrorAwareInterface
{
    use ErrorTrait;

    /**
     * Configuration type, e.g.: migration, list, view, etc.
     *
     * @var string
     */
    protected $configType;

    /**
     * Name of the module
     *
     * @var string
     */
    protected $module;

    /**
     * Name (or other reference) to particular configuration file
     *
     * @var string
     */
    protected $configFile;

    /**
     * Options for finding, parsing, and verifying
     *
     * @var array
     */
    protected $options;

    /**
     * Constructor
     *
     * @param \Qobo\Utils\ModuleConfig\ConfigType $configType Type of configuration
     * @param string $module     Module name
     * @param string $configFile (Optional) name of the config file
     * @param array  $options    (Optional) Finding, parsing, etc. options
     */
    public function __construct(ConfigType $configType, $module, $configFile = '', array $options = [])
    {
        $this->configType = (string)$configType;
        $this->module = (string)$module;
        $this->configFile = (string)$configFile;
        $this->options = $options;
    }

    /**
     * Get path finder instance
     *
     * @return \Qobo\Utils\ModuleConfig\PathFinder\PathFinderInterface
     */
    protected function getFinder()
    {
        $result = ClassFactory::create($this->configType, ClassType::FINDER(), $this->options);

        return $result;
    }

    /**
     * Get parser instance
     *
     * @return \Qobo\Utils\ModuleConfig\Parser\ParserInterface
     */
    protected function getParser()
    {
        $result = ClassFactory::create($this->configType, ClassType::PARSER(), $this->options);

        return $result;
    }

    /**
     * Find module configuration file
     *
     * @param bool $validate Whether or not validate result
     * @return mixed Whatever the PathFinder returned
     */
    public function find($validate = true)
    {
        $cache = $finder = $exception = $cacheKey = $result = null;
        try {
            // Cached response
            $cache = new Cache(__FUNCTION__, $this->options);
            $cacheKey = $cache->getKey([$this->module, $this->configType, $this->configFile, $validate]);
            $result = $cache->readFrom($cacheKey);
            if ($result !== false) {
                return $result;
            }
            // Real response
            $finder = $this->getFinder();
            $result = $finder->find($this->module, $this->configFile, $validate);
        } catch (Exception $exception) {
            $this->mergeMessages($exception, __FUNCTION__);
        }

        // Get finder errors and warnings, if any
        $this->mergeMessages($finder, __FUNCTION__);
        $this->mergeMessages($cache, __FUNCTION__);

        // Re-throw finder exception
        if ($exception) {
            throw $exception;
        }

        if ($cache && $cacheKey) {
            $cache->writeTo($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Parse module configuration file
     *
     * @return object Whatever Parser returned
     */
    public function parse()
    {
        $cache = $parser = $exception = $cacheKey = $result = null;
        try {
            $path = $this->find(false);
            // Cached response
            $cache = new PathCache(__FUNCTION__, $this->options);
            $cacheKey = $cache->getKey([$path]);
            $result = $cache->readFrom($cacheKey);
            if ($result !== false) {
                return $result;
            }
            // Real response
            $parser = $this->getParser();
            $result = $parser->parse($path, $this->options);
        } catch (Exception $exception) {
            $this->mergeMessages($exception, __FUNCTION__);
        }

        // Get parser errors and warnings, if any
        $this->mergeMessages($parser, __FUNCTION__);
        $this->mergeMessages($cache, __FUNCTION__);

        // Re-throw parser exception
        if ($exception) {
            throw $exception;
        }

        if ($cache && $cacheKey) {
            $cache->writeTo($cacheKey, $result, ['path' => $path]);
        }

        return $result;
    }

    /**
     * Format messages
     *
     * Format and prefix all given messages with a given string.
     *
     * @param string|array $messages One or more messages to prefix
     * @param string $prefix Prefix to prepend to all messages
     * @return array List of prefixed messages
     */
    protected function formatMessages($messages, $prefix)
    {
        $result = [];

        $prefix = (string)$prefix;

        // Convert single messages to array
        if (is_string($messages)) {
            $messages = [$messages];
        }

        // Prefix all messages
        foreach ($messages as $message) {
            $result[] = sprintf("[%s][%s] %s : %s", $this->module, $this->configType, $prefix, $message);
        }

        return $messages;
    }

    /**
     * Merge warning and error messages
     *
     * Merge warning and error messages from a given source
     * object into our warnings and messages.
     *
     * @param object $source Source object (ideally one using ErrorTrait)
     * @param string $caller Caller that generated a message
     * @return void
     */
    protected function mergeMessages($source, $caller = 'ModuleConfig')
    {
        $source = is_object($source) ? $source : new stdClass();

        if ($source instanceof Exception) {
            $this->errors = array_merge($this->errors, $this->formatMessages($source->getMessage(), $caller));

            return;
        }

        if ($source instanceof ErrorAwareInterface) {
            $this->errors = array_merge($this->errors, $this->formatMessages($source->getErrors(), $caller));
            $this->warnings = array_merge($this->warnings, $this->formatMessages($source->getWarnings(), $caller));

            return;
        }

        $this->errors[] = "Cannot merge messages from [" . get_class($source) . "]";
    }
}
