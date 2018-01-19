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
namespace Qobo\Utils\ModuleConfig\Parser\Ini;

use stdClass;

/**
 * Config INI Parser
 *
 * This parser is useful for module config INI processing.
 *
 * @author Leonid Mamchenkov <l.mamchenkov@qobo.biz>
 */
class ConfigParser extends AbstractIniParser
{
    /**
     * JSON schema
     *
     * This can either be a string, pointing to the file
     * or an stdClass with an instance of an already parsed
     * schema
     *
     * @var string|\stdClass $schema JSON schema
     */
    protected $schema = 'file://' . __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Schema' . DIRECTORY_SEPARATOR . 'config.json';

    /**
     * Default sections and configuration values
     *
     * @var array
     */
    protected $defaults = [
        'table' => [
            'icon' => 'cube',
            'searchable' => true,
            'lookup_fields' => [],
            'typeahead_fields' => [],
            'basic_search_fields' => [],
            'allow_reminders' => [],
            'translatable' => false,
            'permissions_parent_modules' => [],
        ],
        'virtualFields' => [],
        'associations' => [
            'hide_associations' => [],
        ],
        'associationLabels' => [],
        'notifications' => [
            'enable' => false,
            'ignored_fields' => [],
        ],
        'manyToMany' => [
            'modules' => [],
        ],
    ];

    /**
     * Merge with default values
     *
     * @param object $data Data to merge with defaults
     * @return object
     */
    protected function mergeWithDefaults($data = null)
    {
        // Set defaults
        foreach ($this->defaults as $section => $options) {
            // Make sure the section exists
            if (!property_exists($data, $section)) {
                $data->$section = new stdClass();
            }
            // Make sure default values are set
            foreach ($options as $option => $value) {
                if (!property_exists($data->$section, $option)) {
                    $data->$section->$option = $value;
                }
            }
        }

        // Convert CSV string values to arrays
        $data->table->lookup_fields = $this->csv2array($data->table->lookup_fields);
        $data->table->typeahead_fields = $this->csv2array($data->table->typeahead_fields);
        $data->table->permissions_parent_modules = $this->csv2array($data->table->permissions_parent_modules);
        $data->table->basic_search_fields = $this->csv2array($data->table->basic_search_fields);
        $data->table->allow_reminders = $this->csv2array($data->table->allow_reminders);
        $data->associations->hide_associations = $this->csv2array($data->associations->hide_associations);
        $data->notifications->ignored_fields = $this->csv2array($data->notifications->ignored_fields);
        $data->manyToMany->modules = $this->csv2array($data->manyToMany->modules);

        // [table]display_field
        if (!property_exists($data->table, 'display_field')) {
            $this->warnings = array_merge($this->warnings, ["'display_field' is not set in 'table' section"]);
        }

        $virtualFields = json_decode(json_encode($data->virtualFields), true);
        foreach ($virtualFields as $virtualField => $realFields) {
            $data->virtualFields->$virtualField = $this->csv2array($realFields);
        }

        return $data;
    }

    /**
     * Convert a comma-separated string to array
     *
     * If provided $csv is not a string, return as is.
     *
     * @param string $csv String to convert
     * @return array
     */
    protected function csv2array($csv)
    {
        if (!is_string($csv)) {
            return $csv;
        }

        $result = explode(',', $csv);

        return $result;
    }
}
