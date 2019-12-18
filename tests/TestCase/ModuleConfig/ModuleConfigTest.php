<?php
namespace Qobo\Utils\Test\TestCase\ModuleConfig;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\ModuleConfig;
use Qobo\Utils\ModuleConfig\Parser\Parser;
use Qobo\Utils\ModuleConfig\Parser\ParserInterface;
use Qobo\Utils\ModuleConfig\Parser\Schema;
use Qobo\Utils\Utility\Convert;

class ModuleConfigTest extends TestCase
{
    protected $dataDir;

    public function setUp()
    {
        $this->dataDir = dirname(dirname(__DIR__)) . DS . 'data' . DS . 'Modules' . DS;
        Configure::write('CsvMigrations.modules.path', $this->dataDir);
        Configure::write('ModuleConfig.classMapVersion', 'V3');
    }

    /**
     * @return mixed[]
     */
    public function optionsProvider(): array
    {
        return [
            ['skip cache', [ 'cacheSkip' => true ]],
            ['with cache', [' cacheSkip' => false]],
        ];
    }

    /**
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testFind(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::MODULE(), 'Foo', null, $options);
        $path = $mc->find();
        $this->assertFalse(empty($path), "Path is empty [$path]");
        $this->assertTrue(is_string($path), "Path is not a string [$path]");
        $this->assertTrue(file_exists($path), "Path does not exist [$path]");
        $this->assertTrue(is_readable($path), "Path is not readable [$path]");
        $this->assertTrue(is_file($path), "Path is not a file [$path]");
    }

    /**
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testFindOther(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::MODULE(), 'Foo', 'other_config.ini', $options);
        $path = $mc->find();
        $this->assertFalse(empty($path), "Path is empty [$path]");
        $this->assertTrue(is_string($path), "Path is not a string [$path]");
        $this->assertTrue(file_exists($path), "Path does not exist [$path]");
        $this->assertTrue(is_readable($path), "Path is not readable [$path]");
        $this->assertTrue(is_file($path), "Path is not a file [$path]");
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testFindNotFoundException(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::MODULE(), 'Foo', 'this_file_is_not.there', $options);
        $path = $mc->find();
    }

    /**
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testFindNoValidation(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::MODULE(), 'Foo', 'this_file_is_not.there', $options);
        $path = $mc->find(false);
        $this->assertFalse(empty($path), "Path is empty [$path]");
        $this->assertTrue(is_string($path), "Path is not a string [$path]");
        $this->assertFalse(file_exists($path), "Path does not exist [$path]");
        $this->assertFalse(is_readable($path), "Path is not readable [$path]");
        $this->assertFalse(is_file($path), "Path is not a file [$path]");
    }

    /**
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testParse(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::MODULE(), 'Foo', null, $options);
        $result = null;
        try {
            $result = $mc->parse();
        } catch (InvalidArgumentException $e) {
            print_r($mc->getErrors());
            $this->fail($e->getMessage());
        }
        $this->assertTrue(is_object($result), "Result is not an object");
        $result = Convert::objectToArray($result);
        $this->assertFalse(empty($result), "Result is empty");
    }

    /**
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testParseToArray(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::MODULE(), 'Foo', null, $options);
        $resultObject = null;
        $resultArray = null;
        try {
            $resultObject = $mc->parse();
            $resultArray = $mc->parseToArray();
        } catch (InvalidArgumentException $e) {
            print_r($mc->getErrors());
            $this->fail($e->getMessage());
        }
        $this->assertTrue(is_object($resultObject), "Result object is not an object");
        $this->assertTrue(is_array($resultArray), "Result array is not an array");
        $expected = Convert::objectToArray($resultObject);
        $this->assertEquals($expected, $resultArray, "Result object is different from result array");
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testParseInvalidException(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::LISTS(), 'Foo', 'invalid_list.csv', $options);
        $parser = $mc->parse();
    }

    /**
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testGetErrors(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::MODULE(), 'Foo', null, $options);

        // Before parsing
        $result = $mc->getErrors();
        $this->assertTrue(is_array($result), "Errors is not an array before parsing");
        $this->assertTrue(empty($result), "Errors is not empty before parsing");
        // Parsing
        $result = null;
        try {
            $result = $mc->parse();
        } catch (InvalidArgumentException $e) {
            print_r($mc->getErrors());
            $this->fail($e->getMessage());
        }
        // After parsing
        $result = $mc->getErrors();
        $this->assertTrue(is_array($result), "Errors is not an array after parsing");
    }

    /**
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testGetWarnings(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::MODULE(), 'Foo', null, $options);

        // Before parsing
        $result = $mc->getWarnings();
        $this->assertTrue(is_array($result), "Warnings is not an array before parsing");
        $this->assertTrue(empty($result), "Warnings is not empty before parsing");
        // Parsing
        $result = null;
        try {
            $result = $mc->parse();
        } catch (InvalidArgumentException $e) {
            print_r($mc->getErrors());
            $this->fail($e->getMessage());
        }
        // After parsing
        $result = $mc->getErrors();
        $this->assertTrue(is_array($result), "Warnings is not an array after parsing");
    }

    /**
     * @dataProvider optionsProvider
     * @param string $description Options description
     * @param mixed[] $options Array of options
     */
    public function testSetCustomParser(string $description, array $options): void
    {
        $mc = new ModuleConfig(ConfigType::MODULE(), 'Foo', null, $options);
        $parser = new Parser($mc->createSchema());
        $mc->setParser($parser);

        $this->assertSame($parser, $mc->getParser());
    }

    public function testExists(): void
    {
        $this->assertTrue(ModuleConfig::exists('Foo'));
        $this->assertFalse(ModuleConfig::exists('Boo'));
    }

    public function testHasMigrationFields(): void
    {
        $this->assertTrue(ModuleConfig::hasMigrationFields('Foo', []));
        $this->assertTrue(ModuleConfig::hasMigrationFields('Foo', ['id', 'name', 'description']));
        $this->assertFalse(ModuleConfig::hasMigrationFields('Foo', ['id', 'name', 'unknown']));
        $this->assertFalse(ModuleConfig::hasMigrationFields('Foo', ['unknown']));
    }
}
