<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\SignatureInterface;

/**
 * Abstract base class for resolving digest (hash) values for EBICS signatures.
 *
 * Digest resolvers calculate hash values used in EBICS protocol for two purposes:
 * 1. **Sign Digest**: Hash used when signing orders with signature A (authentication).
 *    This is typically the public key digest as required by the EBICS spec.
 * 2. **Confirm Digest**: Hash used in the initialization/confirmation letter (bank letter).
 *    For EBICS 2.x, this is the certificate fingerprint or public key digest.
 *    For EBICS 3.0, this is always the X.509 certificate fingerprint.
 *
 * The implementation differs between EBICS versions:
 * - `DigestResolverV2`: EBICS 2.4 & 2.5 - uses public key digest for signing,
 *   certificate fingerprint (if available) or public key digest for confirmation
 * - `DigestResolverV3`: EBICS 3.0 - always uses X.509 certificate fingerprints
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class DigestResolver
{
    protected CryptService $cryptService;

    public function __construct(CryptService $cryptService)
    {
        $this->cryptService = $cryptService;
    }

    /**
     * Calculate the digest for signing EBICS orders.
     *
     * This digest is used when creating digital signatures for order data
     * using signature A (authentication). The calculation method varies
     * by EBICS version.
     *
     * @param SignatureInterface $signature The signature containing the public key
     * @param string $algorithm Hash algorithm to use (default: 'sha256')
     *
     * @return string The calculated digest as hex string
     */
    abstract public function signDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string;

    /**
     * Calculate the digest for the EBICS initialization/confirmation letter.
     *
     * This digest is displayed to the user for manual verification during
     * the key initialization process (INI/HIA orders). The user compares
     * this hash with the one provided by the bank to verify the authenticity
     * of the public keys exchanged.
     *
     * @param SignatureInterface $signature The signature to calculate the digest for
     * @param string $algorithm Hash algorithm to use (default: 'sha256')
     *
     * @return string The calculated digest as hex string
     */
    abstract public function confirmDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string;
}
