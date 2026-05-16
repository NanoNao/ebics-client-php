<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\SignatureInterface;
use EbicsApi\Ebics\Exceptions\CertificateEbicsException;

/**
 * Digest resolver for EBICS protocol version 3.0.
 *
 * In EBICS 3.0, digest calculation always uses X.509 certificate fingerprints
 * for both signing orders and generating the confirmation letter. Unlike EBICS 2.x,
 * X.509 certificates are mandatory (no fallback to RSA key digests).
 *
 * Key differences from EBICS 2.x:
 * - X.509 certificates are required for all signature operations
 * - Both signDigest and confirmDigest use certificate fingerprints
 * - Confirmation digest returns hex-encoded string (bin2hex of raw bytes)
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class DigestResolverV3 extends DigestResolver
{
    public function signDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string
    {
        $certificateContent = $signature->getCertificateContent();

        if ($certificateContent === null || $certificateContent === '') {
            throw new CertificateEbicsException(
                'Certificate is mandatory for EBICS 3.0.'
            );
        }

        return $this->cryptService->calculateCertificateFingerprint(
            $certificateContent,
            $algorithm
        );
    }

    public function confirmDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string
    {
        $certificateContent = $signature->getCertificateContent();

        if ($certificateContent === null || $certificateContent === '') {
            throw new CertificateEbicsException(
                'Certificate is mandatory for EBICS 3.0.'
            );
        }

        return bin2hex(
            $this->cryptService->calculateCertificateFingerprint(
                $certificateContent,
                $algorithm
            )
        );
    }
}
