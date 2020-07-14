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
namespace Qobo\Utils\Shell\Task;

use Bake\Shell\Task\SimpleBakeTask;
use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\ModuleConfig;
use Webmozart\Assert\Assert;

class ModuleTask extends SimpleBakeTask
{
    /**
     * {@inheritDoc}
     */
    public $pathFragment = 'Module/';

    /**
     * Module name
     * @var string
     */
    protected $moduleName;

    /**
     * Options for ModuleConfig.
     *
     * @var mixed[]
     */
    protected $moduleConfigOptions = [
        'cacheSkip' => true,
    ];

    /**
     * {@inheritDoc}
     */
    public function name()
    {
        return 'module';
    }

    /**
     * {@inheritDoc}
     */
    public function fileName($name)
    {
        return $name . 'Module.php';
    }

    /**
     * {@inheritDoc}
     */
    public function template()
    {
        return 'Qobo/Utils.module';
    }

    /**
     * {@inheritDoc}
     */
    public function main($name = null)
    {
        if (empty($name)) {
            $this->abort('Missing the required `name` parameter.');
        }

        $this->moduleName = $name;
        parent::main($name);
    }

    /**
     * Get template data.
     *
     * @return array
     */
    public function templateData()
    {
        $config = (new ModuleConfig(ConfigType::MODULE(), $this->moduleName, null, $this->moduleConfigOptions))->parseToArray();
        $migration = (new ModuleConfig(ConfigType::MIGRATION(), $this->moduleName, null, $this->moduleConfigOptions))->parseToArray();
        $lists = $this->getModuleLists();
        $fields = (new ModuleConfig(ConfigType::FIELDS(), $this->moduleName, null, $this->moduleConfigOptions))->parseToArray();
        $menus = (new ModuleConfig(ConfigType::MENUS(), $this->moduleName, null, $this->moduleConfigOptions))->parseToArray();
        $views = $this->getModuleViews();

        $data = compact('config', 'migration', 'lists', 'fields', 'menus', 'views');
        $data = $this->runDecorators($data);

        foreach ($data as $key => $value) {
            $data[$key] = var_export($value, true);
        }

        return parent::templateData() + $data;
    }

    /**
     * Run decorator classes before generating a module class file.
     *
     * Allows to modify the array data before its written into the file system.
     *
     * @param mixed[] $data Module class variables
     * @return mixed[]
     */
    protected function runDecorators(array $data): array
    {
        $decorators = Configure::read('Module.decorators', []);
        foreach ($decorators as $className) {
            Assert::classExists($className);
            $decorator = new $className();
            $data = $decorator($this->moduleName, $data);
            Assert::isArray($data, (string)__d('Qobo/Utils', 'The decorator {0} did not return an array.', $className));
        }

        return $data;
    }

    /**
     * Returns an array of all lists of the module and their respective contents.
     *
     * @return mixed[]
     */
    protected function getModuleLists(): array
    {
        $files = $this->getDistinctFiles('lists');
        if (empty($files)) {
            return [];
        }

        $results = [];
        foreach ($files as $file) {
            $contents = (new ModuleConfig(ConfigType::LISTS(), $this->moduleName, $file, $this->moduleConfigOptions))->parseToArray();
            $results[$file] = $contents['items'] ?? [];
        }

        return $results;
    }

    /**
     * Returns an array of all module views and their respective contents.
     *
     * @return mixed[]
     */
    protected function getModuleViews(): array
    {
        $views = $this->getDistinctFiles('views');
        if (empty($views)) {
            return [];
        }

        $results = [];
        foreach ($views as $file) {
            $contents = (new ModuleConfig(ConfigType::VIEW(), $this->moduleName, $file, $this->moduleConfigOptions))->parseToArray();
            $results[$file] = $contents['items'] ?? [];
        }

        return $results;
    }

    /**
     * Returns a list of distict file names from folder, based on `.json` suffix.
     *
     * This will strip out all `.dist.json` and `.json` files, remove these two suffixes and
     * return a unique collection of the result.
     *
     * @param string $subFolder Which json sub folder to read.
     * @return string[]
     */
    protected function getDistinctFiles(string $subFolder): array
    {
        $path = CONFIG . 'Modules' . DS;
        if (!empty($this->params['module-path'])) {
            $path = rtrim($this->params['module-path'], DS) . DS;
        }
        $path .= $this->moduleName . DS . $subFolder . DS;
        $dir = new Folder($path);
        $files = $dir->find('.*\.json');

        $filtered = array_map(function ($value) {
            $value = preg_replace('/\.dist\.json$/', '', $value);
            $value = preg_replace('/\.json$/', '', $value);

            return $value;
        }, $files);

        return array_unique($filtered);
    }

    /**
     * {@inheritDoc}
     *
     * @todo Checks thats the module exists
     */
    public function bake($name)
    {
        return parent::bake($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addOption('module-path', [
            'default' => CONFIG . 'Modules' . DS,
            'help' => 'Override the application path to folder with module json files, which defaults to `config/Modules/`',
        ]);

        return $parser;
    }
}
