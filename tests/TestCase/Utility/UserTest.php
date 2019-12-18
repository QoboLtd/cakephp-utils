<?php
namespace Qobo\Utils\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Qobo\Utils\Utility\User;

class UserTest extends TestCase
{
    /**
     * @dataProvider userInfoProvider
     * @param mixed[] $data User data
     */
    public function testSetGetCurrentUser(array $data): void
    {
        User::setCurrentUser($data);
        $this->assertEquals($data, User::getCurrentUser());
    }

    /**
     * @return mixed[]
     */
    public function userInfoProvider(): array
    {
        return [
            [['id' => 123]],
            [['id' => true]],
            [['id' => '00000000-0000-0000-0000-000000000001']],
            [['id' => 'foo']],
            [[]],
        ];
    }
}
