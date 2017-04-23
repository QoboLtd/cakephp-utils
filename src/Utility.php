<?php
namespace Qobo\Utils;

use Cake\Core\App;
use Cake\Core\Plugin;
use DirectoryIterator;

class Utility
{
    /**
     * Method that returns all controller names.
     *
     * @param bool $includePlugins Flag for including plugin controllers
     * @return array
     */
    public static function getControllers($includePlugins = true)
    {
        // get application controllers
        $result = static::getDirControllers(APP . 'Controller' . DS);

        if (!(bool)$includePlugins) {
            return $result;
        }

        $plugins = Plugin::loaded();
        // get plugins controllers
        foreach ($plugins as $plugin) {
            $path = Plugin::path($plugin) . 'src' . DS . 'Controller' . DS;
            $result = array_merge($result, static::getDirControllers($path, $plugin));
        }

        return $result;
    }

    /**
     * Method that retrieves controller names found on the provided directory path.
     *
     * @param string $path Directory path
     * @param string $plugin Plugin name
     * @param bool $fqcn Flag for using fqcn
     * @return array
     */
    public static function getDirControllers($path, $plugin = null, $fqcn = true)
    {
        $result = [];
        if (!file_exists($path)) {
            return $result;
        }

        $dir = new DirectoryIterator($path);
        foreach ($dir as $fileinfo) {
            $className = $fileinfo->getBasename('.php');

            // skip AppController
            if ('AppController' === $className) {
                continue;
            }

            // skip directories
            if (!$fileinfo->isFile()) {
                continue;
            }

            if (!empty($plugin)) {
                $className = $plugin . '.' . $className;
            }

            if ((bool)$fqcn) {
                $className = App::className($className, 'Controller');
            }

            if (!$className) {
                continue;
            }

            $result[] = $className;
        }

        return $result;
    }
}