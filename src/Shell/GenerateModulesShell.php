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
namespace Qobo\Utils\Shell;

use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Webmozart\Assert\Assert;

/**
 * Generates module classes from json definitions.
 *
 * @property \Qobo\Utils\Shell\Task\ModuleTask $Module
 */
class GenerateModulesShell extends Shell
{
    public $tasks = ['Qobo/Utils.Module'];

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addSubcommand('Module', [
            'help' => $this->Module->getOptionParser()->getDescription(),
            'parser' => $this->Module->getOptionParser(),
        ])->addOption('force', [
            'short' => 'f',
            'boolean' => true,
            'help' => 'Force overwriting existing files without prompting.',
        ])->addOption('module-path', [
            'default' => CONFIG . 'Modules' . DS,
            'help' => 'Override the application path to folder with module json files.',
        ])->addOption('output-path', [
                'default' => '',
                'help' => 'Override the module output path.',
        ])->addOption('skip-decorators', [
            'boolean' => true,
            'default' => false,
            'help' => 'Skip running module decorators',
        ]);

        return $parser;
    }

    /**
     * Shell entry point
     *
     * @return int|bool|null
     */
    public function main()
    {
        $modules = $this->getModules();

        // Generate the classes without apply the decorators first
        // since, some of the decorators may depend on other Module classes
        foreach ($modules as $module) {
            $this->info(sprintf('Generating classes for module %s', $module), 0);
            $this->generateModule(
                $module,
                (string)$this->param('module-path'),
                (string)$this->param('output-path'),
                (bool)$this->param('force'),
                true
            );
            $this->hr();
        }

        if ((bool)$this->param('skip-decorators')) {
            return true;
        }

        // Apply decorators if necessary
        foreach ($modules as $module) {
            $this->info(sprintf('Applying decorators for module %s', $module), 0);
            $this->generateModule(
                $module,
                (string)$this->param('module-path'),
                (string)$this->param('output-path'),
                (bool)$this->param('force'),
                false
            );
            $this->hr();
        }

        return true;
    }

    /**
     * Triggers the module task to generate the class file, for the specified module
     *
     * @param string $module Module's name
     * @param string $modulePath Override the application path to folder with Module JSON files
     * @param string $outputPath Override the output path to folder with Module JSON files
     * @param bool $force Force overwriting existing files without prompting
     * @param bool $skipDecorators Skip running module decorators
     */
    private function generateModule(string $module, string $modulePath, string $outputPath, bool $force, bool $skipDecorators): void
    {
        $command = ['generate_modules', 'module', $module];
        if (!empty($modulePath)) {
            $command[] = '--module-path';
            $command[] = $modulePath;
        }

        if (!empty($outputPath)) {
            $command[] = '--output-path';
            $command[] = $outputPath;
        }

        if ($force) {
            $command[] = '-f';
        }

        if ($skipDecorators) {
            $skipDecorators = true;
            $command[] = '--skip-decorators';
        }

        $this->dispatchShell(compact('command'));
    }

    /**
     * Retrieves all module names.
     *
     * @return string[]
     */
    private function getModules(): array
    {
        $dir = new Folder(CONFIG . 'Modules' . DS);

        $result = $dir->read(true);
        Assert::isArray($result);
        Assert::notEmpty($result[0]); // hold the directories
        Assert::isArray($result[0]);

        return $result[0];
    }
}
