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
namespace Qobo\Utils\ModuleConfig\Parser;

use Cake\Core\InstanceConfigTrait;
use InvalidArgumentException;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Qobo\Utils\ConfigInterface;
use Qobo\Utils\ErrorTrait;
use Qobo\Utils\ModuleConfig\Parser\SchemaInterface;
use Qobo\Utils\Utility;
use Qobo\Utils\Utility\Convert;
use Seld\JsonLint\ParsingException;
use stdClass;

class Parser implements ConfigInterface, ParserInterface
{
    use ErrorTrait;
    use InstanceConfigTrait;

    /**
     * Default configuration.
     * @var array
     */
    protected $_defaultConfig = [
        'pathRequired' => false,
        'allowEmptyData' => true,
        'allowEmptySchema' => true,
        'lint' => false,
        'validate' => false,
        'validationMode' => Constraint::CHECK_MODE_NORMAL,
    ];

    /**
     * JSON schema
     *
     * @var \Qobo\Utils\ModuleConfig\Parser\SchemaInterface $schema JSON schema
     */
    protected $schema;

    /**
     * @var array $options Parsing options
     */
    protected $options = [];

    /**
     * Class constructor.
     *
     * Options:
     * - pathRequired: Will throw an exception when the path cannot be read.
     * - allowEmptyData: Throw an exception when the data is empty.
     * - allowEmptySchema: Throw an exception when the schema is empty.
     * - lint: Whether to lint the json file or not.
     * - validate: Whether json validation should be performed.
     * - validationMode: Validation mode which will be passed to Json validation.
     * See \JsonSchema\Constraints\Constraint::class for more on those.
     *
     * @param \Qobo\Utils\ModuleConfig\Parser\SchemaInterface $schema Schema object.
     * @param mixed[] $options Options.
     */
    public function __construct(SchemaInterface $schema, array $options = [])
    {
        $this->setSchema($schema);
        $this->setConfig($options);
    }

    /**
     * {@inheritDoc}
     */
    public function setSchema(SchemaInterface $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchema(): SchemaInterface
    {
        return $this->schema;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException When data cannot be read from file.
     * @throws \Qobo\Utils\ModuleConfig\Parser\JsonValidationException When json validation fails.
     */
    public function parse(string $path, array $options = []): \stdClass
    {
        $data = $this->getEmptyResult();

        if (!empty($options)) {
            $this->setConfig($options);
        }

        try {
            $result = $this->getDataFromPath($path);
            $data = Convert::dataToJson($result, $this->getConfig('lint'));
            if ($this->getConfig('validate') === true) {
                $this->validate($data);
            }
        } catch (ParsingException $e) {
            $message = sprintf("File: %s\n%s", $path, $e->getMessage());
            $this->errors[] = $message;

            throw new InvalidArgumentException($message, 0, $e);
        } catch (InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();

            throw $e;
        } catch (JsonValidationException $e) {
            $schemaFile = explode(DIRECTORY_SEPARATOR, $path);

            throw new InvalidArgumentException(sprintf('[%s] : %s', end($schemaFile), $e->getMessage()), 0, $e);
        }

        return $data;
    }

    /**
     * Read raw data from path.
     *
     * If the configuration option `pathRequired` is set to `true`, then an
     * exception will be raised if the path cannot be validated.
     *
     * Otherwise a warning will be raised and a string representation of an
     * empty json object will be sent back to the caller.
     *
     * @see \Qobo\Utils\Utility::validatePath()
     * @param string $path Full path to file.
     * @return string File contents.
     */
    protected function getDataFromPath(string $path): string
    {
        $isPathRequired = $this->getConfig('pathRequired');

        try {
            Utility::validatePath($path);
        } catch (InvalidArgumentException $e) {
            if ($isPathRequired) {
                throw $e;
            }
            $this->warnings[] = $e->getMessage();

            return (string)json_encode($this->getEmptyResult());
        }

        return (string)file_get_contents($path);
    }

    /**
     * Validate the JSON object against the schema.
     *
     * @param \stdClass $data JSON object.
     * @throws \Qobo\Utils\ModuleConfig\Parser\JsonValidationException When json validation fails.
     * @return void
     */
    protected function validate(stdClass $data): void
    {
        $config = $this->getConfig();
        $schema = $this->readSchema();

        // No need to validate empty data (empty() does not work on objects)
        $dataArray = Convert::objectToArray($data);
        if (empty($dataArray)) {
            if ($config['allowEmptyData'] === false) {
                throw new JsonValidationException('Empty data is not allowed.');
            }
            $this->warnings[] = "Skipping validation of empty data";

            return;
        }

        // No need to validate with empty schema (empty() does not work on objects)
        $schemaArray = Convert::objectToArray($schema);
        if (empty($schemaArray)) {
            if ($config['allowEmptySchema'] === false) {
                throw new JsonValidationException('Empty schema is not allowed.');
            }
            $this->warnings[] = "Skipping validation with empty schema";

            return;
        }

        $this->runValidator($data, $schema);
    }

    /**
     * Reads the schema.
     *
     * @throws \Qobo\Utils\ModuleConfig\Parser\JsonValidationException When the schema cannot be read.
     * @return \stdClass Schema
     */
    protected function readSchema(): \stdClass
    {
        $schema = $this->getEmptyResult();

        try {
            $schema = $this->getSchema()->read();
        } catch (InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();

            throw new JsonValidationException("Schema file `{$this->schema->getSchemaPath()}` cannot be read", 0, $e);
        }

        return $schema;
    }

    /**
     * Runs the json validation.
     *
     * @param stdClass $data Data to validate against a schema.
     * @param stdClass $schema The schema which is used to validate the data.
     * @throws \Qobo\Utils\ModuleConfig\Parser\JsonValidationException When the validation fails.
     * @return void
     */
    protected function runValidator(stdClass $data, stdClass $schema): void
    {
        $config = $this->getConfig();

        $validator = new Validator();
        $validator->validate($data, $schema, $config['validationMode']);

        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $this->errors[] = sprintf('[%s]: %s', $error['pointer'], $error['message']);
            }

            throw new JsonValidationException('Failed to validate json data against the schema.');
        }
    }

    /**
     * Get empty result
     *
     * @return \stdClass
     */
    protected function getEmptyResult(): \stdClass
    {
        return new stdClass();
    }
}
