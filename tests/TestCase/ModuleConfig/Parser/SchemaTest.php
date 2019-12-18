<?php
namespace Qobo\Utils\Test\TestCase\ModuleConfig\Parser;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Qobo\Utils\ModuleConfig\Parser\Schema;
use Qobo\Utils\Utility;
use Qobo\Utils\Utility\Convert;
use RuntimeException;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use stdClass;

class SchemaTest extends TestCase
{
    /**
     * Path to schema file
     * @var string
     */
    protected $schemaPath;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        // All your dir are belong to us!
        $this->schemaPath = implode(DIRECTORY_SEPARATOR, [
            dirname(dirname(dirname(__DIR__))),
            'data', 'schema',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        unset($this->schemaPath);

        parent::tearDown();
    }

    /**
     * Test the schema
     *
     * @return void
     */
    public function testSchema(): void
    {
        $this->assertTrue(file_exists($this->schemaPath), "Path does not exist: $this->schemaPath");
        $this->assertTrue(is_dir($this->schemaPath), "Path is not a directory: $this->schemaPath");
        $this->assertTrue(is_readable($this->schemaPath), "Path is not readable: $this->schemaPath");

        $schema = $this->getSchema();

        $expectedPath = implode([$this->schemaPath, DIRECTORY_SEPARATOR, 'sample.json']);
        $this->assertEquals($expectedPath, $schema->getSchemaPath());

        try {
            $result = $schema->read();
            $this->assertTrue(is_object($result), 'Result is invalid data type.');
        } catch (InvalidArgumentException $e) {
            $this->fail('No exception was expected.');
        }
    }

    /**
     * Test the schema for file errors
     *
     * @return void
     */
    public function testSchemaFileError(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $schema = $this->getSchema('sample_missing');

        try {
            $schema->read();
        } catch (InvalidArgumentException $e) {
            $this->assertContains('Path does not exist', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Test the schema for parsing errors
     *
     * @return void
     */
    public function testSchemaError(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $schema = $this->getSchema('sample_bad');
        $schema->read();
    }

    /**
     * Test the schema callback
     *
     * @return void
     */
    public function testSchemaCallback(): void
    {
        $this->expectException(RuntimeException::class);

        $schema = $this->getSchema();
        $schema->setCallback(function ($schema) {
            throw new RuntimeException();
        });

        $schema->read();
        $this->fail('Callback did not execute as expected.');
    }

    /**
     * Test the schema callback
     *
     * @return void
     */
    public function testSchemaCallbackForgotToReturnSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $schema = $this->getSchema();
        $schema->setCallback(function ($schema) {
            $schema['foo'] = 'bar'; // intentionally do not return the schema.
        });

        try {
            $schema->read();
        } catch (InvalidArgumentException $e) {
            $this->assertContains('Callback returned `null`', $e->getMessage());

            throw $e;
        }

        $this->fail('Callback did not execute as expected.');
    }

    /**
     * Test the schema callback which amends the schema.
     *
     * @return void
     */
    public function testSchemaCallbackAmendSchema(): void
    {
        $schema = $this->getSchema();
        $schema->setCallback(function (array $schema) {
            $schema['definitions']['testField']['type'] = 'integer';

            return $schema;
        });

        $result = $schema->read();
        $actual = Convert::objectToArray($result);

        $this->assertEquals('integer', $actual['definitions']['testField']['type']);
    }

    /**
     * Test schema linting
     *
     * @return void
     */
    public function testSchemaLint(): void
    {
        $schema = $this->getSchema();
        $schema->setConfig('lint', true);

        $result = $schema->read();
        $this->assertTrue(is_object($result), 'Result is invalid data type.');
    }

    /**
     * Test schema linting with errors
     *
     * @return void
     */
    public function testSchemaLintError(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $schema = $this->getSchema('sample_bad');
        $schema->setConfig('lint', true);

        try {
            $schema->read();
        } catch (InvalidArgumentException $e) {
            $this->assertContains('Parse error on line 9', $e->getMessage(), 'Invalid error occured. Expected parse error.');

            throw $e;
        }
    }

    /**
     * Helpers method to fetch the schema object.
     *
     * @param string $file File name. Defaults to `sample`
     * @return \Qobo\Utils\ModuleConfig\Parser\Schema Schema object
     */
    protected function getSchema(string $file = 'sample'): Schema
    {
        return new Schema(sprintf('%s%s%s%s', $this->schemaPath, DIRECTORY_SEPARATOR, $file, '.json'));
    }
}
