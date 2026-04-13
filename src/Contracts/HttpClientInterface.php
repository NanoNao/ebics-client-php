<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Models\Http\Response;

/**
 * HTTP Client interface for EBICS communication.
 *
 * This interface is conceptually inspired by PSR-18 (HTTP Client),
 * which defines a standard contract for sending HTTP requests and receiving
 * responses. However, this library intentionally does NOT implement PSR-18
 * directly, as PSR-18 requires PSR-7 (HTTP Message) interfaces for Request
 * and Response objects.
 *
 * Instead, HttpClientInterface uses the library's own Request/Response models
 * (EbicsApi\Ebics\Models\Http\Request and Response), keeping the codebase
 * fully self-contained with zero external dependencies.
 *
 * Key differences from PSR-18:
 * - Uses custom Request/Response models instead of PSR-7 MessageInterface
 * - Provides a convenience post() method instead of generic sendRequest()
 * - Accepts a URL string directly instead of extracting URI from a Request object
 *
 * For projects that need PSR-18 compatibility, the library provides
 * PsrHttpClient (src/Services/PsrHttpClient.php) which adapts any
 * PSR-18 client to this interface.
 *
 * EBICS Protocol Context:
 * EBICS communication occurs over HTTPS POST requests. The client sends
 * XML-encoded EBICS requests to the bank's endpoint URL and receives
 * XML responses. The HTTP layer must support:
 * - TLS/SSL encryption for secure transport
 * - POST method with XML body
 * - Proper Content-Type headers (text/xml or application/xml)
 * - Connection timeouts and retry logic
 *
 * For testing, fake implementations (e.g., FakerHttpClient) can return
 * fixture data instead of making real network calls.
 *
 * @see https://www.php-fig.org/psr/psr-18/ PSR-18: HTTP Client
 * @see https://www.php-fig.org/psr/psr-7/ PSR-7: HTTP Message Interfaces
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface HttpClientInterface
{
    /**
     * Send a POST request to the EBICS server and parse the XML response.
     *
     * Transmits the EBICS request XML to the bank's HTTPS endpoint
     * and returns the parsed response. Handles HTTP status codes,
     * connection errors, and XML parsing failures.
     *
     * @param string $url The bank's EBICS endpoint URL
     * @param Request $request The EBICS request object containing XML body and headers
     *
     * @return Response The parsed response from the bank server
     */
    public function post(string $url, Request $request): Response;
}
