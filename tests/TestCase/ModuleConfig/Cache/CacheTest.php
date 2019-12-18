<?php
namespace Qobo\Utils\Test\TestCase\ModuleConfig\Cache;

use Cake\TestSuite\TestCase;
use Qobo\Utils\ModuleConfig\Cache\Cache;

class CacheTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructException(): void
    {
        $result = new Cache('');
    }

    public function testConstruct(): void
    {
        $result = new Cache('test_cache');
        $this->assertTrue(is_object($result), "__construct() returned a non-object result");
    }

    public function testGetConfig(): void
    {
        $expected = Cache::DEFAULT_CONFIG;
        $cache = new Cache('test_cache');
        $result = $cache->getConfig();
        $this->assertEquals($expected, $result, "getConfig() returned a non-default config name: $result");

        $expected = 'my_config';
        $cache = new Cache('test_cache', ['cacheConfig' => $expected]);
        $result = $cache->getConfig();
        $this->assertEquals($expected, $result, "getConfig() returned a wrong config name: $result");
    }

    public function testSkipCache(): void
    {
        $expected = false;
        $cache = new Cache('test_cache');
        $result = $cache->skipCache();
        $this->assertEquals($expected, $result, "skipCache() returned a wrong default value");
        $this->assertTrue(is_bool($result), "skipCache() returned a non-boolean result for default value");

        $expected = true;
        $cache = new Cache('test_cache', ['cacheSkip' => $expected]);
        $result = $cache->skipCache();
        $this->assertEquals($expected, $result, "getConfig() returned a wrong value");
        $this->assertTrue(is_bool($result), "skipCache() returned a non-boolean result for non-default value");
    }

    public function testGetKey(): void
    {
        $options = [
            'cacheSkip' => true,
            'cacheConfig' => 'my_config',
        ];
        $params = [
            'one' => 1,
            'two' => 'two',
            'three' => true,
        ];
        $paramsAll = $params;
        $paramsAll[] = $options;
        $paramsAllJson = json_encode($paramsAll);
        $paramsAllJson = $paramsAllJson ?: '';
        $expected = 'foo_' . md5($paramsAllJson);

        $cache = new Cache('foo', $options);
        $result = $cache->getKey($params);
        $this->assertFalse(empty($result), "getKey() returned an empty result");
        $this->assertTrue(is_string($result), "getKey() returned a non-string result");
        $this->assertEquals($expected, $result, "getKey() returned a wrong key value");
    }

    public function testWriteToRaw(): void
    {
        $cache = new Cache('foo');
        $data = ['array' => 'with no data key'];
        $result = $cache->writeTo('raw_key', $data, ['raw' => true]);
        $this->assertTrue(is_bool($result), "writeTo() returned a non-boolean result");
        $this->assertFalse($result, "writeTo() cached value without 'data' key");
    }
}
