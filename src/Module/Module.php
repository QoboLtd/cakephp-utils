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

use Cake\Utility\Hash;

class Module implements ModuleInterface
{
    /**
     * Config
     * @var mixed[]
     */
    protected $config;

    /**
     * Migration
     * @var mixed[]
     */
    protected $migration;

    /**
     * Fields
     * @var mixed[]
     */
    protected $fields;

    /**
     * Lists, indexed by list name.
     * @var mixed[]
     */
    protected $lists;

    /**
     * Menus, indexed by menu name.
     * @var mixed[]
     */
    protected $menus;

    /**
     * Views, indexed by view name.
     * @var mixed[]
     */
    protected $views;

    /**
     * {@inheritDoc}
     */
    public function getConfig(string $path = ''): array
    {
        if (!empty($path)) {
            return (array)Hash::extract($this->config, $path);
        }

        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function getMigration(string $path = ''): array
    {
        if (!empty($path)) {
            return (array)Hash::extract($this->migration, $path);
        }

        return $this->migration;
    }

    /**
     * {@inheritDoc}
     */
    public function getFields(string $path = ''): array
    {
        if (!empty($path)) {
            return (array)Hash::extract($this->fields, $path);
        }

        return $this->fields;
    }

    /**
     * {@inheritDoc}
     */
    public function getMenus(): array
    {
        return $this->menus;
    }

    /**
     * {@inheritDoc}
     */
    public function getMenu(string $name): array
    {
        if (!isset($this->menus[$name])) {
            return [];
        }

        return $this->menus[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getList(string $name, bool $flatten = false, bool $filter = false): array
    {
        if (!isset($this->lists[$name])) {
            return [];
        }

        $list = $this->lists[$name];
        if ($flatten) {
            $list = $this->flattenList($list);
        }
        if ($filter) {
            $list = $this->filterList($list);
        }

        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function getView(string $name): array
    {
        if (!isset($this->views[$name])) {
            return [];
        }

        return $this->views[$name];
    }

    /**
     * Method that filters list options, excluding non-active ones
     *
     * @param  mixed[]  $data list options
     * @return mixed[]
     */
    protected function filterList(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if ($value['inactive']) {
                continue;
            }

            $result[$key] = $value;
            if (isset($value['children'])) {
                $result[$key]['children'] = $this->filterList($value['children']);
            }
        }

        return $result;
    }

    /**
     * Flatten list options.
     *
     * @param mixed[] $data List options
     * @return mixed[]
     */
    protected function flattenList(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $item = [
                'label' => $value['label'],
                'inactive' => $value['inactive'],
            ];

            $result[$key] = $item;

            if (isset($value['children'])) {
                $result = array_merge($result, $this->flattenList($value['children']));
            }
        }

        return $result;
    }
}
