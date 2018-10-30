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
namespace Qobo\Utils\Utility\Lock;

interface LockInterface
{
    /**
     * lock method
     *
     * @return bool True on success, false otherwise
     */
    public function lock(): bool;

    /**
     * unlock method
     *
     * @return bool True on success, false otherwise
     */
    public function unlock(): bool;
}
