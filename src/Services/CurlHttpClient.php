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
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class CurlHttpClient extends HttpClient implements HttpClientInterface
{
    /**
     * @param array<int, mixed> $options cURL options (CURLOPT_* constants)
     */
    public function __construct(private readonly array $options = [])
    {
    }

    /**
     * @param string $url The bank's EBICS endpoint URL (HTTPS)
     * @param Request $request The EBICS XML request to send
     *
     * @return Response The parsed XML response from the bank
     *
     * @throws TimeoutEbicsException If the request times out or the response is not a string
     * @throws RuntimeException If cURL cannot be initialized
     */
    public function post(string $url, Request $request): Response
    {
        $ch = curl_init($url);
        if (false === $ch) {
            throw new RuntimeException('Can not create curl.');
        }

        $options = $this->options;
        $options[CURLOPT_TIMEOUT] = $options[CURLOPT_TIMEOUT] ?? 30;
        $options[CURLOPT_HTTPHEADER] = ['Content-Type: ' . self::CONTENT_TYPE];
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $request->getContent();
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_FAILONERROR] = true;

        curl_setopt_array($ch, $options);

        $contents = curl_exec($ch);
        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
        }

        if (!is_string($contents)) {
            throw new TimeoutEbicsException(
                sprintf(
                    'EBICS Bank response is not a string. Timeout is %d s. %s',
                    $options[CURLOPT_TIMEOUT],
                    $errorMsg ?? ''
                )
            );
        }

        return $this->createResponse($contents);
    }
}
