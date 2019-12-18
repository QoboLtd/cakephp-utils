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
use Qobo\Utils\ConfigInterface;
use Qobo\Utils\ErrorAwareInterface;
use Qobo\Utils\ErrorTrait;
use Qobo\Utils\ModuleConfig\Cache\Cache;
use Qobo\Utils\ModuleConfig\Cache\PathCache;
use Qobo\Utils\ModuleConfig\Parser\ParserInterface;
use Qobo\Utils\ModuleConfig\Parser\Schema;
use Qobo\Utils\ModuleConfig\Parser\SchemaInterface;
use Qobo\Utils\ModuleConfig\PathFinder\PathFinderInterface;
use Qobo\Utils\Utility\Convert;
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
     * Parser
     *
     * @var \Qobo\Utils\ModuleConfig\Parser\ParserInterface
     */
    protected $parser;

    /**
     * Constructor
     *
     * @param \Qobo\Utils\ModuleConfig\ConfigType $configType Type of configuration
     * @param string $module     Module name
     * @param string $configFile (Optional) name of the config file
     * @param mixed[] $options    (Optional) Finding, parsing, etc. options
     * @param \Qobo\Utils\ModuleConfig\Parser\ParserInterface $parser Custom parser instance.
     */
    public function __construct(ConfigType $configType, string $module, string $configFile = null, array $options = [], ?ParserInterface $parser = null)
    {
        $this->configType = (string)$configType;
        $this->module = $module;
        $this->configFile = (string)$configFile;
        $this->options = $options;
        $this->setParser($parser);
    }

    /**
     * Get path finder instance
     *
     * @return \Qobo\Utils\ModuleConfig\PathFinder\PathFinderInterface
     */
    protected function getFinder(): PathFinderInterface
    {
        $result = ClassFactory::createFinder($this->configType, $this->options);

        return $result;
    }

    /**
     * Get parser instance
     *
     * @return \Qobo\Utils\ModuleConfig\Parser\ParserInterface
     */
    public function getParser(): ParserInterface
    {
        return $this->parser;
    }

    /**
     * Creates a new Schema instance for the current config type.
     *
     * Reads the following configuration option: `ModuleConfig.schemaPath`.
     *
     * @param mixed[] $config Schema config.
     * @return SchemaInterface Schema object.
     */
    public function createSchema(array $config = []): SchemaInterface
    {
        $path = rtrim(Configure::read('ModuleConfig.schemaPath'), '/');
        $file = $this->configType . '.json';
        $schemaPath = implode(DIRECTORY_SEPARATOR, [$path, $file]);

        return new Schema($schemaPath, null, $config);
    }

    /**
     * Set parser
     *
     * @param \Qobo\Utils\ModuleConfig\Parser\ParserInterface|null $parser Parser
     * @return void
     */
    public function setParser(?ParserInterface $parser): void
    {
        if (is_null($parser)) {
            $options = array_merge($this->options, ['classArgs' => [$this->createSchema()]]);

            $parser = ClassFactory::createParser($this->configType, $options);
            if (!empty($this->options) && $parser instanceof ConfigInterface) {
                $parser->setConfig($this->options);
            }
        }

        $this->parser = $parser;
    }

    /**
     * Find module configuration file
     *
     * @param bool $validate Whether or not validate result
     * @return mixed Whatever the PathFinder returned
     */
    public function find(bool $validate = true)
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
        } catch (InvalidArgumentException $exception) {
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
        $result = new stdClass();
        $cache = $parser = $exception = $cacheKey = $path = null;
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
            $result = $parser->parse($path);
        } catch (InvalidArgumentException $exception) {
            $this->mergeMessages($exception, __FUNCTION__);
        }
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
     * Parse module configuration file to associative array
     *
     * @return mixed[] Whatever Parser returned, converted to array
     */
    public function parseToArray(): array
    {
        $result = $this->parse();
        $result = Convert::objectToArray($result);

        return $result;
    }

    /**
     * Format messages
     *
     * Format and prefix all given messages with a given string.
     *
     * @param string|array $messages One or more messages to prefix
     * @param string $prefix Prefix to prepend to all messages
     * @return string[] List of prefixed messages
     */
    protected function formatMessages($messages, string $prefix): array
    {
        $result = [];

        // Convert single messages to array
        if (is_string($messages)) {
            $messages = [$messages];
        }

        // Prefix all messages
        foreach ($messages as $message) {
            $result[] = sprintf("[%s][%s] %s : %s", $this->module, $this->configType, $prefix, $message);
        }

        return $result;
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
    protected function mergeMessages($source = null, string $caller = 'ModuleConfig'): void
    {
        $source = is_object($source) ? $source : new stdClass();

        if ($source instanceof InvalidArgumentException) {
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

    /**
     * Checks if the provided module name exists
     *
     * @param string $moduleName Module name
     * @param mixed[] $options Options for ModuleConfig constructor
     * @return bool
     */
    public static function exists(string $moduleName, array $options = []): bool
    {
        $config = (new ModuleConfig(ConfigType::MIGRATION(), $moduleName, null, $options))->parseToArray();
        if (empty($config)) {
            return false;
        }

        $config = (new ModuleConfig(ConfigType::MODULE(), $moduleName, null, $options))->parseToArray();
        if (empty($config['table']) || empty($config['table']['type'])) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether the provided fields exists in migration configuration
     *
     * @param string $moduleName Module name
     * @param mixed[] $fields List of fields to be checked
     * @param mixed[] $options Options for ModuleConfig constructor
     * @return bool
     */
    public static function hasMigrationFields(string $moduleName, array $fields, array $options = []): bool
    {
        $config = (new ModuleConfig(ConfigType::MIGRATION(), $moduleName, null, $options))->parseToArray();

        $fieldKeys = array_flip($fields);
        $diff = array_diff_key($fieldKeys, $config);

        return empty($diff);
    }
}
