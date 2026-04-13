<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\DigestResolverInterface;
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
abstract class DigestResolver implements DigestResolverInterface
{
    public function __construct(protected readonly CryptService $cryptService)
    {
    }

    abstract public function signDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string;

    abstract public function confirmDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string;
}
