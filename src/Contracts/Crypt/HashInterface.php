<?php

namespace EbicsApi\Ebics\Contracts\Crypt;

/**
 * Cryptographic Hash interface.
 *
 * Provides a unified abstraction over various hash algorithms used in
 * EBICS protocol operations, including SHA-256, SHA-512, and others.
 *
 * EBICS Protocol Context:
 * Hash functions are fundamental to EBICS security:
 * - Computing digests of order data for RSA signature generation (A005, A006)
 * - Creating transaction signatures (X002) over request headers
 * - Verifying data integrity during transmission
 *
 * EBICS 2.5 and 3.0 primarily use SHA-256 (256-bit) for most operations,
 * while A006 (RSA-PSS) may use SHA-512 for enhanced security.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface HashInterface
{
    /**
     * Compute the cryptographic hash of the given text.
     *
     * @param string $text The input data to hash
     *
     * @return string The raw binary hash output
     */
    public function hash($text);

    /**
     * Get the output length of the hash algorithm in bytes.
     *
     * Returns the size of the hash output (e.g., 32 bytes for SHA-256,
     * 64 bytes for SHA-512). This is useful for allocating buffers
     * and validating hash lengths.
     *
     * @return int The hash output length in bytes
     */
    public function getLength();
}
