<?php
namespace Qobo\Utils\Test\App;

/**
 * Sample module decorator
 */
class SampleModuleDecorator
{
    /**
     * Undocumented function
     *
     * @param mixed[] $data Incoming module data
     * @return mixed[]
     */
    public function __invoke(array $data): array
    {
        if (isset($data['config']['table']['alias'])) {
            if ($data['config']['table']['alias'] === 'Foobar') {
                $data['config']['table']['alias'] = 'Decorated Foobar';
            }
        }

        return $data;
    }
}
