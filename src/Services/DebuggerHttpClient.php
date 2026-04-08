<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\HttpClientInterface;
use EbicsApi\Ebics\Exceptions\DebuggerException;
use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Models\Http\Response;

/**
 * Debug HTTP client that throws exceptions with request data for inspection.
 *
 * This client is used during development and debugging to inspect the exact
 * XML requests that would be sent to the bank server without actually
 * making network calls. When `post()` is called, it throws a `DebuggerException`
 * containing the URL and request body, allowing developers to examine the
 * EBICS XML messages.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class DebuggerHttpClient implements HttpClientInterface
{
    /**
     * Throw a DebuggerException containing the request details for inspection.
     *
     * @param string $url The target URL that would have been called
     * @param Request $request The EBICS XML request that would have been sent
     *
     * @return never
     * @throws DebuggerException Always thrown with request data for debugging
     */
    public function post(string $url, Request $request): Response
    {
        throw new DebuggerException($url, $request->getContent());
    }
}
