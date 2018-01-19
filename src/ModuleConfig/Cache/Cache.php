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
namespace Qobo\Utils\ModuleConfig\Cache;

use Cake\Cache\Cache as CakeCache;
use InvalidArgumentException;
use Qobo\Utils\ErrorAwareInterface;
use Qobo\Utils\ErrorTrait;

/**
 * Cache Class
 *
 * This class caches values consistently as an asociative
 * array with the value being stored in the 'data' key.
 *
 * @author Leonid Mamchenkov <l.mamchenkov@qobo.biz>
 */
class Cache implements ErrorAwareInterface
{
    use ErrorTrait;

    /**
     * Name of default CakePHP cache configuration
     */
    const DEFAULT_CONFIG = 'default';

    /**
     * Name of the current cache instance
     *
     * This is mostly useful for prefixing the keys
     * of the cache to avoid any name clashing.
     *
     * @var string $name Name of the cache
     */
    protected $name;

    /**
     * Associative array of options
     *
     * @var array $options
     */
    protected $options;

    /**
     * CakePHP Cache configuration name
     *
     * @var string $configName
     */
    protected $configName;

    /**
     * Skip caching altogether
     *
     * @var bool $skipCache
     */
    protected $skipCache;

    /**
     * Required keys for the cached value
     *
     * Configuration for validating cached values.  An
     * associative array of keys to check for presence,
     * and the boolean for whether or not empty values
     * are allowed.
     *
     * @param array $requiredKeys Cached value validation
     */
    protected $requiredKeys = [
            'data' => false,
        ];

    /**
     * Constructor
     *
     * @throws \InvalidArgumentException when no name given
     * @param string $name Name of the current cache instance (think prefix)
     * @param array $options Options
     */
    public function __construct($name, array $options = [])
    {
        $this->setName($name);
        $this->setOptions($options);
    }

    /**
     * Set cache instance name
     *
     * @throws \InvalidArgumentException when name is empty
     * @param string $name Name of cache instance
     * @return void
     */
    protected function setName($name)
    {
        $name = (string)$name;
        if (empty($name)) {
            throw new InvalidArgumentException("Cache name is required and cannot be empty");
        }

        $this->name = $name;
    }

    /**
     * Set cache options
     *
     * @param array $options Cache options
     * @return void
     */
    protected function setOptions(array $options = [])
    {
        $this->options = $options;
        $this->configName = empty($options['cacheConfig']) ? static::DEFAULT_CONFIG : (string)$options['cacheConfig'];
        $this->skipCache = empty($this->options['cacheSkip']) ? false : (bool)$this->options['cacheSkip'];
    }

    /**
     * Get cache configuration name
     *
     * @return string
     */
    public function getConfig()
    {
        return $this->configName;
    }

    /**
     * Check if the caching should be skipped or not
     *
     * @return bool True if skipping, false otherwise
     */
    public function skipCache()
    {
        return $this->skipCache;
    }

    /**
     * Generate cache key
     *
     * In order to avoid hardcoding any particular values
     * in the cache key, we instead rely on a given array
     * of parameters.  The array will be converted to
     * a string via json_encode() and shortened using an
     * md5 checksum.
     *
     * For those cases where json_encode() fails, the current
     * microtime() will be used, as it's better to have at
     * least some cache key than nothing at all.
     *
     * Name of the current cache instance is used as a prefix.
     * And just for convenience, the array of current instance
     * options is appended to key parameters.
     *
     * @param array $params Parameters for key generation
     * @return string
     */
    public function getKey(array $params)
    {
        // Push current options to the list of
        // params to ensure unique cache key for
        // each set of options.
        $params[] = $this->options;

        $params = json_encode($params);
        $params = $params ?: microtime();
        $params = md5($params);

        $result = $this->name . '_' . $params;

        return $result;
    }

    /**
     * Read cached value from a given key
     *
     * @param string $key Cache key
     * @return mixed False on failure, cached value otherwise
     */
    public function readFrom($key)
    {
        $result = false;

        if ($this->skipCache()) {
            $this->warnings[] = 'Skipping read from cache';

            return $result;
        }

        $cachedData = CakeCache::read($key, $this->getConfig());
        if (!$this->isValidCache($cachedData)) {
            CakeCache::delete($key, $this->getConfig());

            return $result;
        }
        $result = $cachedData['data'];

        return $result;
    }

    /**
     * Write a value to cache key
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param array $params Additional parameters
     * @return bool False on failure, true on success
     */
    public function writeTo($key, $data, array $params = [])
    {
        $result = false;

        if ($this->skipCache()) {
            $this->warnings[] = 'Skipping writing to cache';

            return $result;
        }

        $cachedData['data'] = $data;
        $raw = empty($params['raw']) ? false : (bool)$params['raw'];
        if ($raw) {
            if (!array_key_exists('data', $data)) {
                $this->errors[] = "Raw data is missing 'data' key";

                return $result;
            }
            $cachedData = $data;
        }

        $result = CakeCache::write($key, $cachedData, $this->getConfig());
        if (!$result) {
            $this->errors[] = 'Failed to write value to cache';
        }

        return $result;
    }

    /**
     * Validate the value
     *
     * @param mixed $value Value to check
     * @return bool False if invalid, true if valid
     */
    protected function isValidCache($value)
    {
        $result = false;

        if ($value === false) {
            $this->warnings[] = 'Value not found in cache';

            return $result;
        }

        if (!is_array($value)) {
            $this->errors[] = "Cached value is not an array";

            return $result;
        }

        foreach ($this->requiredKeys as $key => $notEmpty) {
            // Key exists
            if (!array_key_exists($key, $value)) {
                $this->errors[] = "Cached value is missing '$key'";

                return $result;
            }
            // Value is not empty
            if ($notEmpty && empty($value[$key])) {
                $this->errors[] = "Cached value is empty for key '$key'";

                return $result;
            }
        }

        return true;
    }
}
