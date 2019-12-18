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
use Qobo\Utils\Utility;
use Qobo\Utils\Utility\Convert;
use Seld\JsonLint\ParsingException;
use stdClass;

class Schema implements SchemaInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration
     * @var array
     */
    protected $_defaultConfig = [
        'lint' => false,
    ];

    /**
     * Full to schema file
     * @var string
     */
    protected $schemaPath;

    /**
     * Cached schema.
     * @var \stdClass
     */
    protected $schema;

    /**
     * Has the schema been loaded
     * @var bool
     */
    protected $loaded = false;

    /**
     * Callback to execute before returning the schema.
     * @var callable|null
     */
    protected $callback = null;

    /**
     * Class constructor.
     *
     * Options:
     * - lint: Enable linting the schema.
     *
     * @param string $path Path to json schema file.
     * @param callable|null $callback Optional callable.
     * @param mixed[] $options Options.
     */
    public function __construct(string $path, ?callable $callback = null, array $options = [])
    {
        $this->setSchemaPath($path);
        $this->setCallback($callback);
        $this->setConfig($options);
    }

    /**
     * {@inheritDoc}
     */
    public function getSchemaPath(): string
    {
        return $this->schemaPath;
    }

    /**
     * {@inheritDoc}
     */
    public function setSchemaPath(string $path): void
    {
        $this->schemaPath = $path;
    }

    /**
     * {@inheritDoc}
     */
    public function setCallback(?callable $callback): void
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritDoc}
     */
    public function read(): stdClass
    {
        if (!$this->loaded) {
            try {
                Utility::validatePath($this->schemaPath);
                $data = (string)file_get_contents($this->schemaPath);
                $this->schema = Convert::dataToJson($data, $this->getConfig('lint'));
                $this->loaded = true;
            } catch (ParsingException $e) {
                throw new InvalidArgumentException(sprintf("Schema file: %s\n%s", $this->schemaPath, $e->getMessage()), 0, $e);
            }
        }

        $data = $this->applyCallback($this->schema);

        return $data;
    }

    /**
     * Apply the optional callback.
     *
     * @param \stdClass $schema JSON schema object.
     * @return \stdClass JSON schema object after callback processing.
     */
    protected function applyCallback(stdClass $schema): stdClass
    {
        if (is_callable($this->callback)) {
            $result = call_user_func_array($this->callback, [Convert::objectToArray($schema)]);

            if (is_null($result)) {
                throw new InvalidArgumentException('Callback returned `null`. Did you forget to `return $schema;`?');
            }

            if ($result !== false) {
                return Convert::arrayToObject($result);
            }
        }

        return $schema;
    }
}
