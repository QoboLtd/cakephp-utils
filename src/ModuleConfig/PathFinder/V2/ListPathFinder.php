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
namespace Qobo\Utils\ModuleConfig\PathFinder\V2;

use Qobo\Utils\ModuleConfig\PathFinder\BasePathFinder;

/**
 * ListPathFinder Class
 *
 * This path finder is here to assist with finding
 * the paths to the list files.
 *
 * @author Leonid Mamchenkov <l.mamchenkov@qobo.biz>
 */
class ListPathFinder extends BasePathFinder
{
    /**
     * @var string $extension Default file extension
     */
    protected $extension = '.json';

    /**
     * @var string $prefix Path prefix
     */
    protected $prefix = 'lists';

    /**
     * Find path
     *
     * Find path to a given list.  Make sure that $path
     * parameter is required, and, if the value is
     * given without the file extension, attach one to make it
     * easier to find.
     *
     * @param string $module Module to look for files in
     * @param string $path   Path to look for
     * @param bool   $validate Validate existence of the result
     * @return null|string|array Null for not found, string for single path, array for multiple paths
     */
    public function find(string $module, string $path = '', bool $validate = true)
    {
        $this->validatePath($path);
        $path = $this->addFileExtension($path);

        return parent::find($module, $path, true);
    }
}
