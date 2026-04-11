<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Exceptions\TimeoutEbicsException;
use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Services\CurlHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Simplified unit tests for CurlHttpClient.
 *
 * Tests exception handling without relying on network access.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class CurlHttpClientTest extends TestCase
{
    private CurlHttpClient $client;

    protected function setUp(): void
    {
        $this->client = new CurlHttpClient();
    }

    /**
     * Test post throws TimeoutEbicsException on DNS failure.
     */
    public function testPostThrowsExceptionOnInvalidUrl(): void
    {
        $request = new Request();
        $request->loadXML('<?xml version="1.0"?><root/>');

        $this->expectException(TimeoutEbicsException::class);
        $this->expectExceptionMessage('EBICS Bank response is not a string');

        $this->client->post('https://invalid-host-that-does-not-exist.local/ebics', $request);
    }
}
