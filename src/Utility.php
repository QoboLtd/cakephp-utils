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
namespace Qobo\Utils;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Database\Exception;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\Filesystem\Folder;
use Cake\Utility\Inflector;
use DirectoryIterator;
use InvalidArgumentException;
use UnexpectedValueException;

class Utility
{
    /**
     * Check validity of the given path
     *
     * @throws \InvalidArgumentException when path does not exist or is not readable
     * @param string $path Path to validate
     * @return void
     */
    public static function validatePath(string $path = null): void
    {
        if (empty($path)) {
            throw new InvalidArgumentException("Cannot validate empty path");
        }

        if (!file_exists($path)) {
            throw new InvalidArgumentException("Path does not exist [$path]");
        }
        if (!is_readable($path)) {
            throw new InvalidArgumentException("Path is not readable [$path]");
        }
    }

    /**
     * Method that returns all controller names.
     *
     * @param bool $includePlugins Flag for including plugin controllers
     * @return string[]
     */
    public static function getControllers(bool $includePlugins = true): array
    {
        // get application controllers
        $result = static::getDirControllers(APP . 'Controller' . DS);

        if ($includePlugins === false) {
            return $result;
        }

        $plugins = Plugin::loaded();
        if (!is_array($plugins)) {
            return $result;
        }

        // get plugins controllers
        foreach ($plugins as $plugin) {
            $path = Plugin::path($plugin) . 'src' . DS . 'Controller' . DS;
            $result = array_merge($result, static::getDirControllers($path, $plugin));
        }

        return $result;
    }

    /**
     * Get Api Directory Tree with prefixes
     *
     * @param string $path for setting origin directory
     *
     * @return mixed[] $apis with all versioned api directories
     */
    public static function getApiVersions(string $path = ''): array
    {
        $apis = [];
        $apiPath = (!empty($path)) ? $path : App::path('Controller/Api')[0];

        $dir = new Folder();
        // get folders in Controller/Api directory
        $tree = $dir->tree($apiPath, false, 'dir');

        foreach ($tree as $treePath) {
            if ($treePath === $apiPath) {
                continue;
            }

            $path = str_replace($apiPath, '', $treePath);

            preg_match('/V(\d+)\/V(\d+)/', $path, $matches);
            if (empty($matches)) {
                continue;
            }

            unset($matches[0]);
            $number = implode('.', $matches);

            $apis[] = [
                'number' => $number,
                'prefix' => self::getApiRoutePrefix($matches),
                'path' => self::getApiRoutePath($number),
            ];
        }

        if (!empty($apis)) {
            $apis = self::sortApiVersions($apis);
        }

        return $apis;
    }

    /**
     * Method that retrieves controller names found on the provided directory path.
     *
     * @param string $path Directory path
     * @param string $plugin Plugin name
     * @param bool $fqcn Flag for using fqcn
     * @return string[]
     */
    public static function getDirControllers(string $path, string $plugin = null, bool $fqcn = true): array
    {
        $result = [];

        try {
            static::validatePath($path);
            $dir = new DirectoryIterator($path);
        } catch (InvalidArgumentException $e) {
            return $result;
        } catch (UnexpectedValueException $e) {
            return $result;
        }

        foreach ($dir as $fileinfo) {
            // skip directories
            if (!$fileinfo->isFile()) {
                continue;
            }

            $className = $fileinfo->getBasename('.php');

            // skip AppController
            if ('AppController' === $className) {
                continue;
            }

            if (!empty($plugin)) {
                $className = $plugin . '.' . $className;
            }

            if ($fqcn) {
                $className = App::className($className, 'Controller');
            }

            if ($className) {
                $result[] = $className;
            }
        }

        return $result;
    }

    /**
     * Get All Models
     *
     * Fetch the list of database models escaping phinxlog
     *
     * @param string $connectionManager to know which schema to fetch
     * @param bool $excludePhinxlog flag to exclude phinxlog tables.
     *
     * @return mixed[] $result containing the list of models from database
     */
    public static function getModels(string $connectionManager = 'default', bool $excludePhinxlog = true): array
    {
        $result = [];
        $tables = ConnectionManager::get($connectionManager)->getSchemaCollection()->listTables();

        if (empty($tables)) {
            return $result;
        }

        foreach ($tables as $table) {
            if ($excludePhinxlog && preg_match('/phinxlog/', $table)) {
                continue;
            }

            $result[$table] = Inflector::humanize($table);
        }

        return $result;
    }

    /**
     * Get Model Columns
     *
     * @param string $model name of the table
     * @param string $connectionManager of the datasource
     *
     * @return mixed[] $result containing key/value pairs of model columns.
     */
    public static function getModelColumns(string $model = '', string $connectionManager = 'default'): array
    {
        $result = $columns = [];

        if (empty($model)) {
            return $result;
        }

        // making sure that model is in table naming conventions.
        $model = Inflector::tableize($model);

        try {
            $connection = ConnectionManager::get($connectionManager);
            $schema = $connection->getSchemaCollection();
            $table = $schema->describe($model);
            $columns = $table->columns();
        } catch (MissingDatasourceConfigException $e) {
            return $result;
        } catch (Exception $e) {
            return $result;
        }

        // A table with no columns?
        if (empty($columns)) {
            return $result;
        }

        foreach ($columns as $column) {
            $result[$column] = $column;
        }

        return $result;
    }

    /**
     * Get a list of directories from a given path (non-recursive)
     *
     * @param string $path Path to look in
     * @return string[] List of directory names
     */
    public static function findDirs(string $path): array
    {
        $result = [];

        try {
            static::validatePath($path);
            $path = new DirectoryIterator($path);
        } catch (InvalidArgumentException $e) {
            return $result;
        } catch (UnexpectedValueException $e) {
            return $result;
        }

        foreach ($path as $dir) {
            if ($dir->isDot()) {
                continue;
            }
            if (!$dir->isDir()) {
                continue;
            }
            $result[] = $dir->getFilename();
        }
        asort($result);

        return $result;
    }

    /**
     * Get colors for Select2 dropdown
     *
     * @param mixed[] $config containing colors array
     * @param bool $pretty to append color identifiers to values.
     *
     * @return mixed[] $result containing colors list.
     */
    public static function getColors(array $config = [], bool $pretty = true): array
    {
        $result = [];

        $config = empty($config) ? Configure::read('Colors') : $config;

        if (!$pretty) {
            return $config;
        }

        if (!$config) {
            return $result;
        }

        foreach ($config as $k => $v) {
            $result[$k] = '<div><div style="width:20px;height:20px;margin:0;border:1px solid #eee;float:left;background:' . $k . ';"></div>&nbsp;&nbsp;' . $v . '</div><div style="clear:all"></div>';
        }

        return $result;
    }

    /**
     * Get Fontawesome icons based on config/icons.php
     *
     * @param mixed[] $config from Cake\Core\Configure containing icon resource
     *
     * @return mixed[] $result with list of icons.
     */
    public static function getIcons(array $config = []): array
    {
        $result = [];

        $requiredIconParams = [
            'url',
            'pattern',
            'default',
        ];

        // passing default icons if no external config present.
        $config = empty($config) ? Configure::read('Icons') : $config;

        if (empty($config)) {
            return $result;
        }

        $diff = array_diff($requiredIconParams, array_keys($config));
        if (!empty($diff)) {
            return $result;
        }

        $data = file_get_contents($config['url']);
        $data = $data ?: '';
        preg_match_all($config['pattern'], $data, $matches);

        if (empty($matches[1])) {
            return $result;
        }

        $result = array_unique($matches[1]);

        if (!empty($config['ignored'])) {
            $result = array_diff($result, $config['ignored']);
        }
        sort($result);

        return $result;
    }

    /**
     * Get icon URL for a given file type (png, jpg, etc)
     *
     * Returns the path to the icon file, ready to be used with
     * HtmlHelper::image().  If the icon for the given type or
     * size does not exists, the fallback blank icon is returned.
     *
     * @throws \InvalidArgumentException when the icon does not exist
     * @param string $type File extension for which to get the icon
     * @param string $size Size of the icon (example: '48px')
     *
     * @return string URL for the icon file
     */
    public static function getFileTypeIcon(string $type, string $size = '48px'): string
    {
        $defaultIcon = '_blank';
        $defaultSize = '48px';

        // Map some file types to existing icons
        $iconMap = [
            'docx' => 'doc',
            'pptx' => 'ppt',
            'pptm' => 'ppt',
            'jpeg' => 'jpg',
        ];

        $type = trim(strtolower($type));
        if (empty($type)) {
            $type = $defaultIcon;
        }

        if (!empty($iconMap[$type])) {
            $type = $iconMap[$type];
        }

        if (empty($size)) {
            $size = $defaultSize;
        }

        $path = implode(DS, [
            Plugin::path('Qobo/Utils'),
            'webroot',
            'img',
            'icons',
            'files',
            '%s',
            '%s.png',
        ]);
        $url = 'Qobo/Utils.icons/files/%s/%s.png';

        $iconPath = sprintf($path, $size, $type);
        $iconUrl = sprintf($url, $size, $type);
        // Fallback on same icon in default size
        if (!file_exists($iconPath)) {
            $iconPath = sprintf($path, $defaultSize, $type);
            $iconUrl = sprintf($url, $defaultSize, $type);
        }
        // Fallback on default icon in same size
        if (!file_exists($iconPath)) {
            $iconPath = sprintf($path, $size, $defaultIcon);
            $iconUrl = sprintf($url, $size, $defaultIcon);
        }
        // Fallback on default icon in default size
        if (!file_exists($iconPath)) {
            $iconPath = sprintf($path, $defaultSize, $defaultIcon);
            $iconUrl = sprintf($url, $defaultSize, $defaultIcon);
        }
        // Something is really wrong (icon files moved to a different location?)
        if (!file_exists($iconPath)) {
            throw new InvalidArgumentException("File type icon does not exist for type [$type] at size [$size]");
        }

        return $iconUrl;
    }

    /**
     * Get API Route path
     *
     * @param string $version of the path
     * @return string with prefixes api path version.
     */
    protected static function getApiRoutePath(string $version): string
    {
        return '/api/v' . $version;
    }

    /**
     * Get API Route prefix
     *
     * @param string[] $versions that contain subdirs of prefix
     * @return string with combined API routing.
     */
    protected static function getApiRoutePrefix(array $versions): string
    {
        return 'api/v' . implode('/v', $versions);
    }

    /**
     * Sorting API Versions ascendingly
     *
     * @param mixed[] $versions of found API sub-directories
     *
     * @return mixed[] $versions sorted in ascending order.
     */
    protected static function sortApiVersions(array $versions = []): array
    {
        usort($versions, function ($first, $second) {
            $firstVersion = (float)$first['number'];
            $secondVersion = (float)$second['number'];

            if ($firstVersion == $secondVersion) {
                return 0;
            }

            return ($firstVersion > $secondVersion) ? 1 : -1;
        });

        return $versions;
    }

    /**
     * getCountryByIp
     *
     * @param string $clientIp to detect country
     * @return string
     */
    public static function getCountryByIp(string $clientIp): string
    {
        $clientCountryCode = '';
        if (function_exists('geoip_country_code_by_name')) {
            $clientCountryCode = geoip_country_code_by_name($clientIp);
        }

        return $clientCountryCode;
    }
}
