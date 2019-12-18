<?php
namespace Qobo\Utils\Test\TestCase\ModuleConfig\Parser;

use Cake\Core\Configure;
use PHPUnit\Framework\TestCase;
use Qobo\Utils\ModuleConfig\Parser\ListParser;
use Qobo\Utils\ModuleConfig\Parser\Schema;
use Qobo\Utils\Utility\Convert;

class ListParserTest extends TestCase
{
    protected $parser;
    protected $dataDir;

    public function setUp()
    {
        $schemaPath = implode(DIRECTORY_SEPARATOR, [
            Configure::read('ModuleConfig.schemaPath'),
            'list.json',
        ]);
        $schema = new Schema($schemaPath);

        $this->parser = new ListParser($schema);
        $this->dataDir = TESTS . 'data' . DS . 'Modules';

        Configure::write('CsvMigrations.modules.path', $this->dataDir);
        Configure::write('ModuleConfig.classMapVersion', 'V2');
    }

    public function tearDown()
    {
        unset($this->parser);
        unset($this->dataDir);
    }

    public function testParse(): void
    {
        $file = $this->dataDir . DS . 'Foo' . DS . 'lists' . DS . 'local_genders.json';
        $result = $this->parser->parse($file);

        $resultArray = Convert::objectToArray($result);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('items', $resultArray);
        $this->assertEquals($resultArray['items']['m']['label'], 'M - Male');
    }

    public function testFilter(): void
    {
        $file = $this->dataDir . DS . 'Foo' . DS . 'lists' . DS . 'local_genders.json';
        $result = $this->parser->parse($file, ['filter' => true]);

        $resultArray = Convert::objectToArray($result);
        $this->assertTrue(!in_array('foo', array_keys($resultArray['items'])));
    }

    public function testFlatten(): void
    {
        $file = $this->dataDir . DS . 'Foo' . DS . 'lists' . DS . 'local_genders.json';
        $result = $this->parser->parse($file, ['filter' => true, 'flatten' => true]);

        $resultArray = Convert::objectToArray($result);

        $this->assertTrue(in_array('bar.bar_one', array_keys($resultArray['items'])));
        $this->assertTrue(in_array('bar.bar_two', array_keys($resultArray['items'])));
    }
}
