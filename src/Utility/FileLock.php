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
use NinjaMutex\Lock\FlockLock;
use Qobo\Utils\Utility\BaseLock;

class FileLock extends BaseLock
{
    /**
     * @var $lockDir
     */
    private $lockDir = null;

    /**
     * __construct method
     *
     * @param string $key to use to lock operation
     * @param string $dir to store lock files
     * @return void
     */
    public function __construct($key, $dir = null)
    {
        $config = Configure::read('Locker.FileLocker');
        if (!empty($config['dir'])) {
            $this->lockDir = $config['dir'];
        }

        if (!empty($dir)) {
            $this->lockDir = $dir;
        }

        if (empty($this->lockDir)) {
            $this->lockDir = sys_get_temp_dir();
        }

        $lock = new FlockLock($this->lockDir);

        parent::__construct($key, $lock);
    }
}
