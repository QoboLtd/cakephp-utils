<?php
namespace Qobo\Utils\Test\TestCase;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Qobo\Utils\Utility;
use Webmozart\Assert\Assert;

class UtilityTest extends TestCase
{
    public $fixtures = [
        'plugin.CakeDC/Users.Users',
    ];

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatePathExceptionEmpty(): void
    {
        Utility::validatePath('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatePathExceptionNotExist(): void
    {
        Utility::validatePath('/some/non/existing/path');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatePathExceptionNotReadable(): void
    {
        Utility::validatePath('/etc/shadow');
    }

    public function testGetControllers(): void
    {
        $result = Utility::getControllers();
        $this->assertTrue(is_array($result), "Result is not an array");
        // Should return at least the Users controller
        $this->assertFalse(empty($result), "Result is empty");

        $result = Utility::getControllers(false);
        $this->assertTrue(is_array($result), "Result is not an array");
        // Should return at least the Users controller
        $this->assertFalse(empty($result), "Result is empty");
    }

    public function testGetDirControllers(): void
    {
        $result = Utility::getDirControllers('/some/non/existing/path');
        $this->assertTrue(is_array($result), "Result is not an array");
        $this->assertTrue(empty($result), "Result is not empty");

        $testApp = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Controller';

        // plugin=false, fqcn=true
        $result = Utility::getDirControllers($testApp);
        $this->assertTrue(is_array($result), "Result is not an array (plugin=false,fqcn=true)");
        $this->assertFalse(empty($result), "Result is not empty (plugin=false,fqcn=true)");
        // Make sure non-fqcn controllers are not in the list
        $this->assertFalse(in_array('UsersController', $result), "Non-fqcn UsersController is in the list (plugin=false,fqcn=true)");
        // Convert fqcn to non-fqcn
        foreach ($result as $key => $item) {
            Assert::string($item);
            $parts = explode('\\', $item);
            $result[$key] = array_pop($parts);
        };
        // Make sure fqcn controllers are in the list
        $this->assertTrue(in_array('UsersController', $result), "Test app UsersController is not in the list (plugin=false,fqcn=true)");
        $this->assertFalse(in_array('AppController', $result), "Test app AppController is in the list (plugin=false,fqcn=true)");

        // plugin=false, fqcn=false
        $result = Utility::getDirControllers($testApp, null, false);
        $this->assertTrue(is_array($result), "Result is not an array (plugin=false,fqcn=false)");
        $this->assertFalse(empty($result), "Result is not empty (plugin=false,fqcn=false)");
        $this->assertTrue(in_array('UsersController', $result), "Test app UsersController is not in the list (plugin=false,fqcn=false)");
        $this->assertFalse(in_array('AppController', $result), "Test app AppController is in the list (plugin=false,fqcn=false)");

        // plugin=Blah, fqcn=false
        $result = Utility::getDirControllers($testApp, 'Blah', false);
        $this->assertTrue(is_array($result), "Result is not an array (plugin=Blah,fqcn=false)");
        $this->assertFalse(empty($result), "Result is not empty (plugin=Blah,fqcn=false)");
        $this->assertTrue(in_array('Blah.UsersController', $result), "Test app UsersController is not in the list (plugin=Blah,fqcn=false)");
        $this->assertFalse(in_array('Blah.AppController', $result), "Test app AppController is in the list (plugin=Blah,fqcn=false)");
    }

    public function testGetModels(): void
    {
        $result = Utility::getModels('test');
        $this->assertTrue(is_array($result));
    }

    public function testGetModelColumns(): void
    {
        // Missing model name
        $result = Utility::getModelColumns('', 'test');
        $this->assertTrue(is_array($result));
        $this->assertEmpty($result);

        // Missing db configuration
        $result = Utility::getModelColumns('Users', 'no such db config');
        $this->assertTrue(is_array($result));
        $this->assertEmpty($result);

        // Missing db table
        $result = Utility::getModelColumns('NoSuchTable', 'test');
        $this->assertTrue(is_array($result));
        $this->assertEmpty($result);

        $result = Utility::getModelColumns('Users', 'test');
        $this->assertTrue(is_array($result));
        $this->assertFalse(empty($result['id']), "Missing 'id' column key in Users model");
        $this->assertEquals('id', $result['id'], "Missing 'id' column value in Users model");
    }

    public function testFindDirs(): void
    {
        // Proper path
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Modules';
        $result = Utility::findDirs($path);
        $this->assertTrue(is_array($result));
        $this->assertFalse(empty($result));
        $this->assertTrue(in_array('Common', $result), "Failed to find Common directory");
        $this->assertTrue(in_array('Foo', $result), "Failed to find Foo directory");
        $this->assertFalse(in_array('.', $result), "Failed to remove dot directory");

        // Path with no directories
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'Foo' . DIRECTORY_SEPARATOR . 'db';
        $result = Utility::findDirs($path);
        $this->assertTrue(is_array($result));
        $this->assertTrue(empty($result));

        // Path to file, not a directory
        $path = __FILE__;
        $result = Utility::findDirs($path);
        $this->assertTrue(is_array($result));
        $this->assertTrue(empty($result));

        // Invalid path
        $path = 'this_path_does_not_exist';
        $result = Utility::findDirs($path);
        $this->assertTrue(is_array($result));
        $this->assertTrue(empty($result));
    }

    /**
     * @dataProvider getIconProvider
     */
    public function testGetIcons(string $configFile, bool $isArray, bool $isEmpty): void
    {
        $config = Configure::read($configFile);
        $result = Utility::getIcons($config);

        $this->assertEquals(is_array($result), $isArray);
        $this->assertEquals(empty($result), $isEmpty);
    }

    /**
     * @return mixed[]
     */
    public function getIconProvider(): array
    {
        return [
            ['Icons', true, false],
        ];
    }

    public function testGetColors(): void
    {
        $config = Configure::read('Colors');
        $result = Utility::getColors($config);

        $this->assertTrue(is_array($result));
        $this->assertNotEmpty($result);

        $result = Utility::getColors($config, false);
        $this->assertTrue(is_array($result));
        $this->assertNotEmpty($result);
    }

    public function testGetApiVersions(): void
    {
        $testDataPath = dirname(dirname(__FILE__)) . DS . 'data';

        $versions = Utility::getApiVersions($testDataPath);

        $this->assertTrue(is_array($versions));
        $this->assertNotEmpty($versions);
    }

    public function testGetFileTypeIcon(): void
    {
        // Default icon
        $expected = 'Qobo/Utils.icons/files/48px/_blank.png';

        $result = Utility::getFileTypeIcon('');
        $this->assertEquals($expected, $result, "Invalid icon returned for empty type, default size");

        $result = Utility::getFileTypeIcon('', '');
        $this->assertEquals($expected, $result, "Invalid icon returned for empty type, empty size");

        $result = Utility::getFileTypeIcon('zzzzz');
        $this->assertEquals($expected, $result, "Invalid icon returned for unsupported type, default size");

        $result = Utility::getFileTypeIcon('zzzzz', '123');
        $this->assertEquals($expected, $result, "Invalid icon returned for unsupported type, unsupported size");

        // Known icon
        $expected = 'Qobo/Utils.icons/files/48px/png.png';

        $result = Utility::getFileTypeIcon('png');
        $this->assertEquals($expected, $result, "Invalid icon returned for supported type, default size");

        $result = Utility::getFileTypeIcon('png', '');
        $this->assertEquals($expected, $result, "Invalid icon returned for supported type, empty size");

        $result = Utility::getFileTypeIcon('png', '123');
        $this->assertEquals($expected, $result, "Invalid icon returned for supported type, unsupported size");

        // Mapped icon
        $expected = 'Qobo/Utils.icons/files/48px/jpg.png';

        $result = Utility::getFileTypeIcon('jpeg');
        $this->assertEquals($expected, $result, "Invalid icon returned for mapped type, default size");
    }

    public function testGetCountryByIpPrivate(): void
    {
        $clientIp = '192.168.57.103'; // non-public
        $this->assertEmpty(Utility::getCountryByIp($clientIp), 'Failed to receive empty country code by non-public IP');
    }

    public function testGetCountryByIpPublic(): void
    {
        $expected = 'CY';
        if (!function_exists('geoip_country_code_by_name')) {
            $expected = '';
        }

        $clientIp = '82.102.92.178'; // CY
        $this->assertEquals(Utility::getCountryByIp($clientIp), $expected, 'Failed to receive correct code by public IP');
    }
}
