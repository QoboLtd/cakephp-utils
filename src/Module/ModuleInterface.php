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
namespace Qobo\Utils\Module;

interface ModuleInterface
{
    /**
     * Returns the module config.
     *
     * @param string $path Optional path to extract.
     * @return mixed[]
     */
    public function getConfig(string $path = ''): array;

    /**
     * Returns the module migration.
     *
     * @param string $path Optional path to extract.
     * @return mixed[]
     */
    public function getMigration(string $path = ''): array;

    /**
     * Returns the module fields config.
     *
     * @param string $path Optional path to extract.
     * @return mixed[]
     */
    public function getFields(string $path = ''): array;

    /**
     * Returns the module menus config.
     *
     * @param string $name Menu name.
     * @return mixed[]
     */
    public function getMenu(string $name): array;

    /**
     * Returns all the module menus.
     *
     * @return mixed[]
     */
    public function getMenus(): array;

    /**
     * Returns the full contents of the list or a subset specifiec by path.
     *
     * @param string $name List name.
     * @param bool $filter Filter the list.
     * @param bool $flatten Flatten the list.
     * @return mixed[]
     */
    public function getList(string $name, bool $filter = false, bool $flatten = false): array;

    /**
     * Returns a module view.
     *
     * @param string $name View name.
     * @return mixed[]
     */
    public function getView(string $name): array;
}
