<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Models\Http\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * HTTP client adapter for PSR-18 HTTP client integration.
 *
 * This client allows the EBICS library to use any PSR-18 compliant HTTP client
 * instead of the built-in CurlHttpClient. This is useful when:
 * - You want to use your framework's HTTP client (e.g., Symfony HttpClient, Guzzle)
 * - You need custom middleware, logging, or HTTP client configuration
 * - You want consistent HTTP client usage across your application
 *
 * To use this client, install `psr/http-client` and a PSR-18 implementation.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Ronan GIRON <https://github.com/ElGigi>
 */
final class PsrHttpClient extends HttpClient
{
    /**
     * Constructor.
     *
     * @param ClientInterface $client PSR-18 HTTP client instance
     * @param RequestFactoryInterface $requestFactory PSR-17 request factory
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory
    ) {
    }

    /**
     * Send an EBICS XML request using the PSR-18 HTTP client.
     *
     * Creates a PSR-7 request with the appropriate Content-Type header and
     * sends it via the configured PSR-18 client.
     *
     * @param string $url The bank's EBICS endpoint URL
     * @param Request $request The EBICS XML request to send
     *
     * @return Response The parsed XML response from the bank
     * @throws ClientExceptionInterface If the HTTP client encounters an error
     */
    public function post(string $url, Request $request): Response
    {
        // Construct PSR request
        $psrRequest = $this->requestFactory
            ->createRequest('POST', $url)
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withBody($this->streamFactory->createStream($request->getContent()));

        // Call PSR HTTP client
        $psrResponse = $this->client->sendRequest($psrRequest);
        $contents = $psrResponse->getBody()->getContents();

        return $this->createResponse($contents);
    }
}
