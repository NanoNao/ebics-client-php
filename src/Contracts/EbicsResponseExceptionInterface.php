<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Models\Http\Response;

/**
 * EBICS Response Exception interface.
 *
 * Represents an error returned by the EBICS bank server during a
 * transaction. Provides access to the EBICS error code, human-readable
 * description, and the raw request/response that caused the error.
 *
 * EBICS Protocol Context:
 * When an EBICS request fails, the bank returns an XML response containing
 * an error code (e.g., "061001" for invalid user ID) and optionally a
 * meaning description. This exception encapsulates that information along
 * with the original request and response for diagnostic purposes.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Guillaume Sainthillier
 */
interface EbicsResponseExceptionInterface
{
    /**
     * Get the EBICS error code returned by the bank.
     *
     * The response code is a six-digit numeric code defined in the
     * EBICS specification, indicating the specific error condition.
     *
     * @return string The EBICS error code (e.g., "061001")
     */
    public function getResponseCode(): string;

    /**
     * Get the human-readable error description from the bank.
     *
     * The meaning is an optional textual explanation of the error code,
     * provided by the bank server. May be null if the bank does not
     * supply a description.
     *
     * @return string|null The error description, or null if unavailable
     */
    public function getMeaning(): ?string;

    /**
     * Get the EBICS request that caused this error.
     *
     * Returns the original request object sent to the bank, useful
     * for debugging and diagnostics.
     *
     * @return Request|null The request object, or null if unavailable
     */
    public function getRequest(): ?Request;

    /**
     * Get the full response from the bank server.
     *
     * Returns the complete response object received from the bank,
     * including the error details and any additional diagnostic data.
     *
     * @return Response|null The response object, or null if unavailable
     */
    public function getResponse(): ?Response;
}
