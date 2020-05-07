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
namespace Qobo\Utils\ModuleConfig\PathFinder;

use Cake\Core\Configure;
use InvalidArgumentException;
use Qobo\Utils\ErrorTrait;
use Qobo\Utils\Utility;

abstract class BasePathFinder implements PathFinderInterface
{
    use ErrorTrait;

    /** @var string The filename postfix for distribution files */
    const DIST_FILENAME_POSTFIX = '.dist';

    /**
     * CakePHP configuration key with base path
     *
     * @var string $pathConfigKey
     */
    protected $pathConfigKey = 'CsvMigrations.modules.path';

    /**
     * @var string $prefix Path prefix
     */
    protected $prefix;

    /**
     * @var string $fileName File name
     */
    protected $fileName;

    /**
     * @var string $extension Default file extension
     */
    protected $extension = '';

    /**
     * Find path
     *
     * @param string $module Module to look for files in
     * @param string $path Path to look for
     * @param bool $validate Validate existence of the result
     * @return null|string|array Null for not found, string for single path, array for multiple paths
     * @throws \InvalidArgumentException
     */
    public function find(string $module, string $path = '', bool $validate = true)
    {
        $path = $this->getFilePath($path);

        // Ignore non-distribution files
        if (Configure::read('ModuleConfig.distFilesOnly') && !$this->isDistributionFilePath($path)) {
            $distributionPath = $this->getDistributionFilePath($path);
            return $this->find($module, $distributionPath, $validate);
        }

        $this->validateModule($module);
        $this->validatePath($path);

        $result = '';
        if (!empty($this->pathConfigKey)) {
            $result = Configure::readOrFail($this->pathConfigKey);
        }
        $result .= $module . DIRECTORY_SEPARATOR;

        if (!empty($this->prefix)) {
            $result .= $this->prefix . DIRECTORY_SEPARATOR;
        }

        $result .= $path;

        try {
            Utility::validatePath($result);
        } catch (InvalidArgumentException $e) {
            // Validation failed which means we can not read the provided file
            // Hence, we are trying to load the fallback file
            $distributionPath = $this->getDistributionFilePath($path);

            // We rethrow the exception, only if the validate flag is enabled
            if ($validate && $this->isDistributionFilePath($path)) {
                throw $e;
            }

            // Try to find the distribution file
            if (!$this->isDistributionFilePath($path)) {
                $result = $this->find($module, $distributionPath, $validate);
            }
        }

        return $result;
    }

    /**
     * Validate module string
     *
     * Check that the given module is a non-empty
     * string.
     *
     * @param string $module Module string to check
     * @return void
     */
    protected function validateModule(string $module): void
    {
        if (empty($module)) {
            $this->fail(new InvalidArgumentException("Module is not specified"));
        }
    }

    /**
     * Get file path string
     *
     * If file path is given, return as is. Otherwise
     * fallback on the default file name.
     *
     * @param string $path File path
     * @return string
     */
    protected function getFilePath(string $path): string
    {
        if (empty($path)) {
            $path = $this->fileName;
        }

        return $path;
    }

    /**
     * Get file path string for the corresponding distribution file
     *
     * If distribution file path is given, return as is. Otherwise
     * adjust the provided one.
     *
     * @param string $path File path
     * @return string
     */
    protected function getDistributionFilePath(string $path): string
    {
        $postfix = self::DIST_FILENAME_POSTFIX;

        if (empty($path)) {
            $path = $this->fileName;
        }

        // Check if this is the distribution file path
        $pathinfo = pathinfo($path);
        $postfixIndex = strlen($pathinfo['filename']) - strlen($postfix);
        $isDistributionFile = substr($pathinfo['filename'], $postfixIndex) === $postfix;
        if (!$isDistributionFile) {
            $extension = !empty($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';
            $path = $pathinfo['filename'] . $postfix . $extension;
        }

        return $path;
    }

    /**
     * Returns true only and only if the provided file path is a distribution path
     *
     * @param string $path File path
     * @return bool
     */
    protected function isDistributionFilePath(string $path): bool
    {
        return $this->getDistributionFilePath($path) === $path;
    }

    /**
     * Validate path string
     *
     * Check that the given path is a non-empty
     * string.
     *
     * NOTE: This method is very different from
     *       the Utility::validatePath(), which
     *       checks the filesystem path for
     *       existence.
     *
     * @param string $path Path string to check
     * @return void
     */
    protected function validatePath(string $path): void
    {
        if (empty($path)) {
            $this->fail(new InvalidArgumentException("Path is not specified"));
        }

        if (!is_string($path)) {
            $this->fail(new InvalidArgumentException("Path is not a string"));
        }
    }

    /**
     * Add default extension if path doesn't have one
     *
     * Check if the given path has a file exntesion.
     * If not, add the default one and return the
     * modified path.
     *
     * @param string $path Path
     * @return string
     */
    protected function addFileExtension(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (empty($extension)) {
            $path .= $this->extension;
        }

        return $path;
    }
}
