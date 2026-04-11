<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Models\Http\Response;

/**
 * HTTP Client interface.
 *
 * Defines the contract for HTTP communication with EBICS bank servers.
 * This interface abstracts the transport layer, allowing different HTTP
 * client implementations (cURL, Guzzle, Symfony HttpClient, mock clients
 * for testing) to be swapped in without changing the core EBICS logic.
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
