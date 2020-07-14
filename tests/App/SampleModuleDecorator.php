<?php
namespace Qobo\Utils\Test\App;

/**
 * Sample module decorator
 */
class SampleModuleDecorator
{
    /**
     * Sample decorator which updates the table alias for module `Foobar`
     *
     * @param string $moduleName Module name
     * @param mixed[] $data Incoming module data
     * @return mixed[]
     */
    public function __invoke(string $moduleName, array $data): array
    {
        if (isset($data['config']['table']['alias'])) {
            if ($data['config']['table']['alias'] === 'Foobar') {
                $data['config']['table']['alias'] = 'Decorated Foobar';
            }
        }

        return $data;
    }
}
