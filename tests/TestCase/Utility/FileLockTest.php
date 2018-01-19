<?php
namespace Qobo\Utils\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Qobo\Utils\Utility\FileLock;

class FileLockTest extends TestCase
{
    public function testInit()
    {
        $filename = 'some_random_filename';

        $lock = new FileLock($filename);
        $this->assertInstanceOf(FileLock::class, $lock);
    }

    public function testLockUnlock()
    {
        $filename = 'some_random_filename';

        $lock = new FileLock($filename);
        $this->assertTrue($lock->lock());

        $this->assertTrue($lock->unlock());
    }
}
