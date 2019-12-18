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

use stdClass;

interface SchemaInterface
{

    /**
     * Get the full path to json schema file.
     *
     * @return string Path to schema file.
     */
    public function getSchemaPath(): string;

    /**
     * Sets the full path to json schema file.
     *
     * @param string $path Path to schema file
     * @return void
     */
    public function setSchemaPath(string $path): void;

    /**
     * Set a callback which will be executed on the schema after it is read.
     *
     * @param callable|null $callback Callback
     * @return void
     */
    public function setCallback(?callable $callback): void;

    /**
     * Read the json schema file.
     *
     * @throws \InvalidArgumentException When the the schema file cannot be read
     * or the callback returned a null.
     * @return \stdClass JSON Schema object
     */
    public function read(): stdClass;
}
