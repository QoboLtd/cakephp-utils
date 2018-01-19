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
namespace Qobo\Utils\Utility;

use Cake\Core\Configure;
use NinjaMutex\Lock\LockInterface as MutexLockInterface;
use NinjaMutex\Mutex;
use Qobo\Utils\Utility\LockInterface;

class BaseLock implements LockInterface
{
    /**
     * @var $locker
     */
    private $locker = null;

    /**
     * @var $timeout
     *
     * 1. null if you want blocking lock
     * 2. 0 if you want just lock and go
     * 3. $timeout > 0 if you want to wait for lock some time (in milliseconds)
     */
    protected $timeout = 1000;

    /**
     * __construct method
     *
     * @param string $key to use for lock operation
     * @param MutexLockInterface $lock for lock operation
     */
    public function __construct($key, MutexLockInterface $lock)
    {
        $config = Configure::read('Locker');
        if (!empty($config['timeout'])) {
            $this->timeout = $config['timeout'];
        }
        $this->locker = new Mutex($key, $lock);
    }

    /**
     * lock method
     *
     * @return bool
     */
    public function lock()
    {
        $result = false;

        if ($this->locker->acquireLock($this->timeout)) {
            $result = true;
        }

        return $result;
    }

    /**
     * unlock method
     *
     * @return bool
     */
    public function unlock()
    {
        $result = false;

        if ($this->locker->releaseLock()) {
            $result = true;
        }

        return $result;
    }
}
