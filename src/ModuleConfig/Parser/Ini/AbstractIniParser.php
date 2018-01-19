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
namespace Qobo\Utils\ModuleConfig\Parser\Ini;

use Exception;
use InvalidArgumentException;
use Piwik\Ini\IniReader;
use Qobo\Utils\ModuleConfig\Parser\AbstractParser;
use Qobo\Utils\Utility;

abstract class AbstractIniParser extends AbstractParser
{
    /**
     * Read and parse a given real path
     *
     * @throws \InvalidArgumentException when cannot read or decode path
     * @param string $path Path to read and parse
     * @return object
     */
    protected function getDataFromRealPath($path)
    {
        try {
            $reader = new IniReader();
            $data = $reader->readFile($path);
        } catch (Exception $e) {
            throw new InvalidArgumentException("Failed to read path: $path");
        }

        $data = json_encode($data);
        if ($data === false) {
            throw new InvalidArgumentException("Failed to encode data from path: $path");
        }

        $data = json_decode($data);
        if ($data === null) {
            throw new InvalidArgumentException("Failed to parse path: $path");
        }

        $result = (object)$data;

        return $result;
    }
}
