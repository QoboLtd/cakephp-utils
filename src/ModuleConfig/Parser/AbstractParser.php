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

use Exception;
use InvalidArgumentException;
use League\JsonGuard\Dereferencer;
use League\JsonGuard\Validator;
use Qobo\Utils\ErrorTrait;
use Qobo\Utils\Utility;
use stdClass;

abstract class AbstractParser implements ParserInterface
{
    use ErrorTrait;

    /**
     * JSON schema
     *
     * This can either be a string, pointing to the file
     * or an stdClass with an instance of an already parsed
     * schema
     *
     * @var string|\stdClass $schema JSON schema
     */
    protected $schema;

    /**
     * @var array $options Parsing options
     */
    protected $options = [];

    /**
     * @var bool $isPathRequired Is path required?
     */
    protected $isPathRequired = false;

    /**
     * Read and parse a given real path
     *
     * @param string $path Path to file
     * @return object
     */
    abstract protected function getDataFromRealPath($path);

    /**
     * Read and parse a given path
     *
     * @param string $path Path to file
     * @return object
     */
    protected function getDataFromPath($path)
    {
        $result = $this->getEmptyResult();
        $result = $this->mergeWithDefaults($result);

        try {
            Utility::validatePath($path);
        } catch (Exception $e) {
            if ($this->isPathRequired) {
                throw $e;
            }
            $this->warnings[] = $e->getMessage();

            return $result;
        }

        $result = $this->getDataFromRealPath($path);
        $result = $this->mergeWithDefaults($result);

        return $result;
    }

    /**
     * Get empty result
     *
     * @return \stdClass
     */
    protected function getEmptyResult()
    {
        return new stdClass();
    }

    /**
     * Parse
     *
     * Parses a given file according to the specified options
     *
     * @param string $path    Path to file
     * @param array  $options Options for parsing
     * @return object
     */
    public function parse($path, array $options = [])
    {
        $result = $this->getEmptyResult();

        // Overwrite default options
        if (!empty($options)) {
            $this->options = $options;
        }

        try {
            $result = $this->getDataFromPath($path);
            $schema = $this->getSchema();
            $this->validateData($result, $schema);
        } catch (Exception $e) {
            $this->fail(new InvalidArgumentException("[" . basename($path) . "] : " . $e->getMessage()));
        }

        return $result;
    }

    /**
     * Validate given data against schema
     *
     * @param object $data Data to validate
     * @param object $schema Schema to validate against
     * @return void
     */
    protected function validateData($data, $schema)
    {
        // No need to validate empty data (empty() does not work on objects)
        if (empty((array)$data)) {
            $this->warnings[] = "Skipping validation of empty data";

            return;
        }

        // No need to validate with empty schema (empty() does not work on objects)
        if (empty((array)$schema)) {
            $this->warnings[] = "Skipping validation with empty schema";

            return;
        }

        $validator = new Validator($data, $schema);
        if ($validator->fails()) {
            foreach ($validator->errors() as $error) {
                $this->errors[] = $error->getMessage();
            }
            throw new InvalidArgumentException("Validation failed");
        }
    }

    /**
     * Get schema
     *
     * @return object|null
     */
    protected function getSchema()
    {
        $result = null;
        if (empty($this->schema)) {
            return $result;
        }

        if (is_object($this->schema)) {
            return $this->schema;
        }

        if (is_string($this->schema)) {
            $deref = new Dereferencer();
            $this->schema = $deref->dereference($this->schema);

            return $this->schema;
        }

        $this->fail(new InvalidArgumentException("Schema is not a string or object: " . gettype($this->schema)));
    }

    /**
     * Merge with default values
     *
     * @param object $data Data to merge with defaults
     * @return object
     */
    protected function mergeWithDefaults($data)
    {
        return $data;
    }
}
