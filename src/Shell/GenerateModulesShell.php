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
        ])->addArgument('modules', [
            'required' => false,
            'help' => 'List of modules to generate',
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

        foreach ($modules as $module) {
            $this->info(sprintf('Generate module %s', $module));

            $this->Module->main($module);
        }

        return true;
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

        $list = !isset($this->args[0]) ? false : explode(",", $this->args[0]);
        if ($list) {
            $result[0] = array_filter($result[0], function ($k) use ($list) {
                return in_array($k, $list);
            });
        }

        return $result[0];
    }
}
