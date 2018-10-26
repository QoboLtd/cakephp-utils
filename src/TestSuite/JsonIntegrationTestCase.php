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
namespace Qobo\Utils\TestSuite;

use Cake\TestSuite\IntegrationTestCase;
use Cake\Utility\Security;
use Firebase\JWT\JWT;

/**
 * JsonIntegrationTestCase
 *
 * This class extends CakePHP IntegrationTestCase
 * with a few asssertions that help testing JSON
 * API end-points.
 */
class JsonIntegrationTestCase extends IntegrationTestCase
{
    /**
     * @var array $defaultRequestHeaders Default request headers
     */
    public $defaultRequestHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ];

    /**
     * Generate JWT token for a given user ID
     *
     * @param string $user User ID
     * @return string
     */
    public function getAuthToken($user)
    {
        $result = JWT::encode(
            [
                'sub' => $user,
                'exp' => time() + 604800
            ],
            Security::salt()
        );

        return $result;
    }

    /**
     * Set JSON request headers
     *
     * @param array $headers Headers to set.  If skipped, default headers are used.
     * @param string $user User ID.  If provided, Authorization header will be added with token
     * @return void
     */
    public function setRequestHeaders(array $headers = [], $user = '')
    {
        if (empty($headers)) {
            $headers = $this->defaultRequestHeaders;
        }

        if (!empty($user)) {
            $headers['Authorization'] = 'Bearer ' . $this->getAuthToken($user);
        }

        $this->configRequest(['headers' => $headers]);
    }

    /**
     * Get JSON parsed response from the last request
     *
     * @return mixed The result of json_decode() on response body
     */
    public function getParsedResponse()
    {
        $response = (string)$this->_response->getBody();
        $response = json_decode($response);

        return $response;
    }

    /**
     * Assert successful JSON response
     *
     * This is a shortcut for the following checks:
     *
     * * Last response was OK
     * * Content type of last response was 'application/json'
     * * Response body could be successfully parsed into an object
     * * Response object has 'success' attribute
     * * 'success' attribute is boolean
     * * 'success' attribute is equal to true
     * * 'Response object has 'data' attribute
     *
     * @return void
     */
    public function assertJsonResponseOk()
    {
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = $this->getParsedResponse();
        $this->assertNotNull($response, "Failed to decode JSON response");
        $this->assertTrue(is_object($response), "JSON response is not an object");

        $this->assertObjectHasAttribute('success', $response);
        $this->assertTrue(is_bool($response->success), "Response success is not a boolean");
        $this->assertTrue($response->success, "Response success is not true");

        $this->assertObjectHasAttribute('data', $response);
    }
}