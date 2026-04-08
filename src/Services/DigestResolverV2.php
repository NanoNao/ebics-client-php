<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\SignatureInterface;

/**
 * Digest resolver for EBICS protocol versions 2.4 and 2.5.
 *
 * In EBICS 2.x, digest calculation uses the public key digest (modulus + exponent hash)
 * for signing orders. For the confirmation letter (bank letter), it prefers the
 * X.509 certificate fingerprint if a certificate is available; otherwise, it falls
 * back to the public key digest.
 *
 * Key differences from EBICS 3.0:
 * - X.509 certificates are optional (fall back to RSA key digest)
 * - Confirmation digest returns hex-encoded string (bin2hex of raw bytes)
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class DigestResolverV2 extends DigestResolver
{
    public function signDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string
    {
        return $this->cryptService->calculatePublicKeyDigest($signature, $algorithm);
    }

    public function confirmDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string
    {
        if (($certificateContent = $signature->getCertificateContent())) {
            $digest = $this->cryptService->calculateCertificateFingerprint($certificateContent, $algorithm);
        } else {
            $digest = $this->cryptService->calculatePublicKeyDigest($signature, $algorithm);
        }

        return bin2hex($digest);
    }
}
