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
namespace Qobo\Utils\ModuleConfig\Parser\Csv;

use Exception;
use InvalidArgumentException;
use League\Csv\Reader;
use Qobo\Utils\Utility;

/**
 * View CSV Parser
 *
 * This parser is useful for view CSV processing.
 * View CSV files can vary in their format, depending
 * on the need of the view (for example, we currently
 * support add/edit/view with panels and index view,
 * which is completely different.
 *
 * Therefor, it is assumed that the caller of the
 * parser knows how to best handle the returned results.
 *
 * It is assumed that the first row ALWAYS contains
 * the column headers.
 *
 * @author Leonid Mamchenkov <l.mamchenkov@qobo.biz>
 */
class ViewParser extends AbstractCsvParser
{
    /**
     * JSON schema
     *
     * This can either be a string, pointing to the file
     * or an stdClass with an instance of an already parsed
     * schema
     *
     * @var string|\stdClass $schema JSON schema
     */
    protected $schema = 'file://' . __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Schema' . DIRECTORY_SEPARATOR . 'view.json';

    /**
     * Read and parse a given real path
     *
     * @throws \InvalidArgumentException when cannot read or decode path
     * @param string $path Path to file
     * @return object
     */
    protected function getDataFromRealPath($path)
    {
        $result = $this->getEmptyResult();

        $reader = Reader::createFromPath($path, $this->mode);
        $rows = $reader->setOffset(1)->fetchAll();
        foreach ($rows as $row) {
            $result->items[] = $this->processRow($row, $path);
        }
        $result = $this->mergeWithDefaults($result);

        return $result;
    }
}
