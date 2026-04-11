<?php

namespace EbicsApi\Ebics\Contracts;

/**
 * Digest Resolver interface.
 *
 * Resolves cryptographic hash (digest) values for EBICS signatures,
 * used for both signing operations and bank letter verification.
 *
 * EBICS Protocol Context:
 * Digests (hashes) serve two distinct purposes in EBICS:
 *
 * 1. **Sign Digest**: The hash value used when digitally signing an order
 *    with signature A (authentication). This is typically the public key
 *    digest — a hash of the DER-encoded public key — as required by the
 *    EBICS specification for order authorization.
 *
 * 2. **Confirm Digest**: The hash value displayed in the EBICS bank
 *    initialization letter for manual verification. During the INI/HIA/H3K
 *    setup process, the user prints the bank letter and sends it to the
 *    bank. The bank compares this digest with the one received electronically
 *    to verify key authenticity.
 *
 * The calculation method varies by EBICS version:
 * - **EBICS 2.x (DigestResolverV2)**: Uses public key digest for signing;
 *   certificate fingerprint (if available) or public key digest for confirmation
 * - **EBICS 3.0 (DigestResolverV3)**: Always uses X.509 certificate fingerprints
 *   for both signing and confirmation, providing stronger security guarantees
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface DigestResolverInterface
{
    /**
     * Calculate the digest for signing EBICS orders.
     *
     * This digest is used when creating digital signatures for order data
     * using signature A (authentication). The calculation method varies
     * by EBICS version.
     *
     * @param SignatureInterface $signature The signature containing the public key
     *                                      to compute the digest from
     * @param string $algorithm Hash algorithm to use (default: 'sha256')
     *
     * @return string The calculated digest as raw binary data
     */
    public function signDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string;

    /**
     * Calculate the digest for the EBICS initialization/confirmation letter.
     *
     * This digest is displayed to the user for manual verification during
     * the key initialization process (INI/HIA/H3K orders). The user compares
     * this hash with the one provided by the bank to verify the authenticity
     * of the exchanged public keys.
     *
     * @param SignatureInterface $signature The signature to calculate the digest for
     * @param string $algorithm Hash algorithm to use (default: 'sha256')
     *
     * @return string The calculated digest as a hexadecimal string
     */
    public function confirmDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string;
}
