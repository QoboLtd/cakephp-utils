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
namespace Qobo\Utils;

/**
 * ErrorAware Interface
 *
 * This interface defines the standard approach for
 * handling errors and warnings.
 *
 * @author Leonid Mamchenkov <l.mamchenkov@qobo.biz>
 */
interface ErrorAwareInterface
{
    /**
     * Get errors
     *
     * @return array List of errors
     */
    public function getErrors();

    /**
     * Get warnings
     *
     * @return array List of warnings
     */
    public function getWarnings();
}
