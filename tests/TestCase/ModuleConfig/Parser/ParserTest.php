<?php
namespace Qobo\Utils\Test\TestCase\ModuleConfig\Parser;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;

use PHPUnit\Framework\TestCase;
use Qobo\Utils\ModuleConfig\Parser\Parser;
use Qobo\Utils\ModuleConfig\Parser\Schema;
use Qobo\Utils\ModuleConfig\Parser\SchemaInterface;
use Qobo\Utils\Utility\Convert;
use stdClass;

class ParserTest extends TestCase
{
    /**
     * Parser instance
     * @var \Qobo\Utils\ModuleConfig\Parser\Parser
     */
    protected $parser;

    /**
     * Directory where sample json files reside.
     * @var string
     */
    protected $dataDir;

    /**
     * Parser instance
     * @var \Qobo\Utils\ModuleConfig\Parser\Schema
     */
    protected $schema;

    /**
     * Path to schema.
     * @var string
     */
    protected $schemaPath;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->dataDir = implode(DIRECTORY_SEPARATOR, [
            dirname(dirname(dirname(__DIR__))),
            'data', 'json'
        ]);

        $this->schemaPath = implode(DIRECTORY_SEPARATOR, [
            dirname(dirname(dirname(__DIR__))),
            'data', 'schema', 'sample.json'
        ]);
        $this->schema = new Schema($this->schemaPath);
        $this->parser = new Parser($this->schema);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        unset($this->parser);
        unset($this->schema);
        unset($this->schemaPath);

        parent::tearDown();
    }

    /**
     * Parse a valid json file.
     *
     * @return void
     */
    public function testParse(): void
    {
        $this->parser->parse($this->getFile());
        $this->assertEmpty($this->parser->getErrors());
    }

    /**
     * Parse an invalid json file.
     *
     * @return void
     */
    public function testParseInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parser->parse(implode([__DIR__, DIRECTORY_SEPARATOR, 'somebadfile.json']));
    }

    /**
     * Parse with an invalid `Schema` object
     *
     * @return void
     */
    public function testParseInvalidSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $schema = new Schema(implode([__DIR__, DIRECTORY_SEPARATOR, 'somebadschema.json']));
        $parser = new Parser($schema);
        $parser->parse($this->getFile());
    }

    /**
     * Parse an invalid json file.
     *
     * @return void
     */
    public function testParseInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parser->parse($this->getFile('sample_error'));
    }

    /**
     * Parse and lint an invalid json file.
     *
     * @return void
     */
    public function testParseLintWithParseError(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->setConfig('lint', true);

        try {
            $this->parser->parse($this->getFile('sample_error'));
        } catch (InvalidArgumentException $e) {
            $this->assertContains('Parse error on line 6', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Parse and validate an bad json file.
     *
     * @return void
     */
    public function testParseValidateWithBadFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // $this->parser->setConfig('lint', true);

        try {
            $this->parser->parse($this->getFile('sample_bad'));
        } catch (InvalidArgumentException $e) {
            $this->assertContains('Failed to validate json', $e->getMessage());
            $this->assertContains('Integer value found, but a string is required', $this->parser->getErrors()[0]);

            throw $e;
        }
    }

    /**
     * Parse and skip on empty data.
     *
     * @return void
     */
    public function testParseSkipEmptyData(): void
    {
        $parser = new Parser($this->getEmptySchema());
        $parser->parse($this->getFile('sample_empty'));
        $this->assertContains('Skipping validation of empty data', $parser->getWarnings()[0]);
    }

    /**
     * Parse and skip on empty schema.
     *
     * @return void
     */
    public function testParseSkipEmptySchema(): void
    {
        $parser = new Parser($this->getEmptySchema());
        $parser->parse($this->getFile('sample'));
        $this->assertContains('Skipping validation with empty schema', $parser->getWarnings()[0]);
    }

    /**
     * Parse and throw on empty data.
     *
     * @return void
     */
    public function testParseThrowEmptyData(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $parser = new Parser($this->getEmptySchema(), [
            'allowEmptyData' => false,
            'allowEmptySchema' => false,
        ]);

        $parser->parse($this->getFile('sample_empty'));
    }

    /**
     * Parse and throw on empty schema.
     *
     * @return void
     */
    public function testParseThrowEmptySchema(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $parser = new Parser($this->getEmptySchema(), [
            'allowEmptyData' => false,
            'allowEmptySchema' => false,
        ]);

        $parser->parse($this->getFile('sample'));
    }

    /**
     * Helper method to fetch the path to json file.
     *
     * @param string $file File name. Defaults to `sample`
     * @return string Full path to file.
     */
    protected function getFile(string $file = 'sample'): string
    {
        return sprintf('%s%s%s%s', $this->dataDir, DIRECTORY_SEPARATOR, $file, '.json');
    }

    /**
     * Returns a mock of SchemaInterface.
     *
     * @return \Qobo\Utils\ModuleConfig\Parser\SchemaInterface Schema mock
     */
    protected function getEmptySchema(): SchemaInterface
    {
        $schemaMock = $this->getMockBuilder(SchemaInterface::class)->getMock();
        $schemaMock->method('read')->willReturn(new stdClass);

        return $schemaMock;
    }
}
