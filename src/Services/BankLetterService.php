<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\SignatureInterface;
use EbicsApi\Ebics\Factories\CertificateX509Factory;
use EbicsApi\Ebics\Factories\SignatureBankLetterFactory;
use EbicsApi\Ebics\Models\SignatureBankLetter;

/**
 * Service for formatting EBICS initialization letters (bank letters).
 *
 * Bank letters contain the public key information and fingerprints that
 * users must verify with their bank during the EBICS initialization process.
 * This service extracts key details (modulus, exponent, hashes) from
 * EBICS signatures and formats them for human-readable display.
 *
 * The formatted data can be rendered in various formats (HTML, TXT, PDF)
 * using the BankLetter formatters for comparison with bank-provided values.
 *
 * Key responsibilities:
 * - Decompose public keys into modulus and exponent components
 * - Format byte data for bank letter display (hex pairs with spacing)
 * - Calculate key hashes for fingerprint verification
 * - Extract X.509 certificate metadata if available
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal This class is for internal use and may change without notice.
 */
final class BankLetterService
{
    /**
     * Constructor.
     *
     * @param CryptService $cryptService Cryptographic service for key operations
     * @param SignatureBankLetterFactory $signatureBankLetterFactory Factory for creating bank letter signatures
     * @param CertificateX509Factory $certificateX509Factory Factory for creating X.509 certificates
     */
    public function __construct(
        private readonly CryptService $cryptService,
        private readonly SignatureBankLetterFactory $signatureBankLetterFactory,
        private readonly CertificateX509Factory $certificateX509Factory
    ) {
    }

    /**
     * Format a signature's public key details for bank letter display.
     *
     * Extracts and formats the key components:
     * - Exponent (formatted as hex pairs)
     * - Modulus (formatted as hex pairs)
     * - Key hash/fingerprint (formatted for human verification)
     * - Certificate metadata if available
     *
     * @param SignatureInterface $signature The EBICS signature to format
     * @param string $version The signature version identifier (e.g., 'A005', 'E002')
     * @param DigestResolver $digestResolver Resolver for calculating key fingerprints
     *
     * @return SignatureBankLetter Formatted signature data ready for bank letter display
     */
    public function formatSignatureForBankLetter(
        SignatureInterface $signature,
        string $version,
        DigestResolver $digestResolver
    ): SignatureBankLetter {
        $publicKeyDetails = $this->cryptService->decomposePublicKey($signature->getPublicKey());

        $exponentFormatted = $this->formatBytesForBank($publicKeyDetails['e']);
        $modulusFormatted = $this->formatBytesForBank($publicKeyDetails['m']);

        $keyHash = $digestResolver->confirmDigest($signature);
        $keyHashFormatted = $this->formatKeyHashForBankLetter($keyHash);

        $modulusSize = strlen($publicKeyDetails['m']) * 8; // 8 bits in byte.
        $signatureBankLetter = $this->signatureBankLetterFactory->create(
            $signature->getType(),
            $version,
            $exponentFormatted,
            $modulusFormatted,
            $keyHashFormatted,
            $modulusSize
        );

        if (($content = $signature->getCertificateContent())) {
            $certificateX509 = $this->certificateX509Factory->createFromContent($content);
            $startDate = $certificateX509->getValidityStartDate();

            $signatureBankLetter->setCertificateContent($content);
            $signatureBankLetter->setCertificateCreatedAt($startDate);
        }

        return $signatureBankLetter;
    }

    /**
     * Format a key hash string for bank letter display.
     *
     * Splits the hash into 2-byte pairs separated by spaces for readability.
     * Example: "A1B2C3D4" becomes "A1 B2 C3 D4"
     *
     * @param string $hash The raw binary hash string
     *
     * @return string Formatted hash ready for display in bank letters
     */
    private function formatKeyHashForBankLetter(string $hash): string
    {
        // Split hash by 2 bytes in array and join by space character.
        $hash = implode(' ', str_split($hash, 2));

        return $hash;
    }

    /**
     * Format bytes to chain of pairs for bank format.
     *
     * @param string $bytes
     *
     * @return string In upper case.
     */
    private function formatBytesForBank(string $bytes): string
    {
        $result = '';

        // Go over pairs of bytes.
        foreach ($this->cryptService->binToArray($bytes) as $byte) {
            // Convert to lover case hexadecimal number and add a space.
            $result .= sprintf('%02x ', $byte);
        }

        return trim($result);
    }
}
