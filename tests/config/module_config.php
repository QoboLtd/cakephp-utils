<?php
use Qobo\Utils\ModuleConfig\ClassType;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\Parser\ListParser;
use Qobo\Utils\ModuleConfig\Parser\Parser;
use Qobo\Utils\ModuleConfig\PathFinder\V2\ConfigPathFinder;
use Qobo\Utils\ModuleConfig\PathFinder\V2\DuplicatesPathFinder;
use Qobo\Utils\ModuleConfig\PathFinder\V2\FieldsPathFinder;
use Qobo\Utils\ModuleConfig\PathFinder\V2\ListPathFinder;
use Qobo\Utils\ModuleConfig\PathFinder\V2\MenusPathFinder;
use Qobo\Utils\ModuleConfig\PathFinder\V2\MigrationPathFinder;
use Qobo\Utils\ModuleConfig\PathFinder\V2\ReportsPathFinder;
use Qobo\Utils\ModuleConfig\PathFinder\V2\ViewPathFinder;

// Class map of finders and parser for each
// support configuration type
$classTypeFinder = (string)ClassType::FINDER();
$classTypeParser = (string)ClassType::PARSER();

return [
    'ModuleConfig' => [
        'schemaPath' => implode(DIRECTORY_SEPARATOR, [
            ROOT,
            'src', 'ModuleConfig', 'Parser', 'Schema',
        ]),
        'classMapVersion' => 'V3',
        'classMap' => [
            'V3' => [
                (string)ConfigType::MIGRATION() => [
                    $classTypeFinder => MigrationPathFinder::class,
                    $classTypeParser => Parser::class,
                ],
                (string)ConfigType::MODULE() => [
                    $classTypeFinder => ConfigPathFinder::class,
                    $classTypeParser => Parser::class,
                ],
                (string)ConfigType::LISTS() => [
                    $classTypeFinder => ListPathFinder::class,
                    $classTypeParser => ListParser::class,
                ],
                (string)ConfigType::FIELDS() => [
                    $classTypeFinder => FieldsPathFinder::class,
                    $classTypeParser => Parser::class,
                ],
                (string)ConfigType::MENUS() => [
                    $classTypeFinder => MenusPathFinder::class,
                    $classTypeParser => Parser::class,
                ],
                (string)ConfigType::REPORTS() => [
                    $classTypeFinder => ReportsPathFinder::class,
                    $classTypeParser => Parser::class,
                ],
                (string)ConfigType::VIEW() => [
                    $classTypeFinder => ViewPathFinder::class,
                    $classTypeParser => Parser::class,
                ],
                (string)ConfigType::DUPLICATES() => [
                    $classTypeFinder => DuplicatesPathFinder::class,
                    $classTypeParser => Parser::class,
                ],
            ],
        ],
    ],
];
