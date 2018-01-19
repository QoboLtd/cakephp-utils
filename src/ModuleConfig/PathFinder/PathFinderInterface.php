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

use Qobo\Utils\ErrorAwareInterface;

/**
 * PathFinderInterface Interface
 *
 * This interface defines the standard approach for
 * finding paths (files and directories) in standard
 * places, with a bit of flexibility for custom
 * situations.
 *
 * @author Leonid Mamchenkov <l.mamchenkov@qobo.biz>
 */
interface PathFinderInterface extends ErrorAwareInterface
{
    /**
     * Find path
     *
     * @param string $module   Module to look for files in
     * @param string $path     Path to look for
     * @param bool   $validate Validate existence of the result
     * @return null|string|array Null for not found, string for single path, array for multiple paths
     */
    public function find($module, $path = null, $validate = true);
}
