<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\HttpClientInterface;
use EbicsApi\Ebics\Exceptions\TimeoutEbicsException;
use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Models\Http\Response;
use RuntimeException;

/**
 * HTTP client using PHP cURL extension for EBICS communication.
 *
 * This is the default HTTP client for the EBICS library. It uses PHP's cURL
 * extension to send XML requests to bank servers over HTTPS.
 *
 * Configuration details:
 * - **Timeout**: 400 seconds (as required by EBICS spec for large file transfers)
 * - **SSL Verification**: Disabled (`CURLOPT_SSL_VERIFYPEER = false`) to accommodate
 *   bank servers with self-signed certificates. In production environments with
 *   proper CA certificates, consider enabling this for improved security.
 * - **Fail on Error**: Enabled (`CURLOPT_FAILONERROR = true`) to throw exceptions
 *   on HTTP 4xx/5xx status codes
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class CurlHttpClient extends HttpClient implements HttpClientInterface
{
    /**
     * Send an EBICS XML request to a bank server via cURL.
     *
     * Posts the XML request body to the specified URL and waits up to 400 seconds
     * for a response. The 400-second timeout accommodates EBICS file download
     * orders that may transfer large amounts of data.
     *
     * @param string $url The bank's EBICS endpoint URL (HTTPS)
     * @param Request $request The EBICS XML request to send
     *
     * @return Response The parsed XML response from the bank
     * @throws TimeoutEbicsException If the request times out or the response is not a string
     * @throws RuntimeException If cURL cannot be initialized
     */
    public function post(string $url, Request $request): Response
    {
        $body = $request->getContent();

        $ch = curl_init($url);
        if (false === $ch) {
            throw new RuntimeException('Can not create curl.');
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . self::CONTENT_TYPE,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $contents = curl_exec($ch);
        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
        }

        if (!is_string($contents)) {
            throw new TimeoutEbicsException(
                'EBICS Bank response is not a string. Timeout is 400s. ' . ($errorMsg ?? '')
            );
        }

        return $this->createResponse($contents);
    }
}
