<?php

namespace EbicsApi\Ebics\Services\BankLetter\Formatter;

use EbicsApi\Ebics\Contracts\BankLetter\FormatterInterface;
use EbicsApi\Ebics\Models\BankLetter;
use EbicsApi\Ebics\Models\SignatureBankLetter;
use LogicException;

/**
 * Abstract base class for bank letter formatters.
 *
 * Provides common functionality for formatting EBICS initialization letters
 * in various output formats (HTML, TXT, PDF). Handles translation support,
 * signature name resolution, and certificate formatting.
 *
 * Concrete implementations:
 * - `HtmlBankLetterFormatter`: Renders as HTML document
 * - `TxtBankLetterFormatter`: Renders as plain text
 * - `PdfBankLetterFormatter`: Renders as PDF document
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal This class is for internal use and may change without notice.
 */
abstract class LetterFormatter implements FormatterInterface
{
    /**
     * @var array|string[]
     */
    protected array $translations
        = [
            'init_letter' => 'Initialization letter',
            'parameters' => 'Parameters',
            'server_name' => 'Server Name',
            'host_id' => 'Host ID',
            'partner_id' => 'Partner ID',
            'user_id' => 'User ID',
            'version' => 'Version',
            'date' => 'Date',
            'auth_certificate' => 'Authentication Certificate',
            'certificate' => 'Certificate',
            'hash' => 'Hash',
            'es_signature' => 'ES signature',
            'encryption_signature' => 'Encryption signature',
            'authentication_signature' => 'Authentication signature',
            'exponent' => 'Exponent',
            'modulus' => 'Modulus',
        ];

    /**
     * @inheritDoc
     */
    public function setTranslations(array $translations): void
    {
        $this->translations = array_replace($this->translations, $translations);
    }

    /**
     * Get server name.
     *
     * @param BankLetter $bankLetter
     *
     * @return string
     */
    protected function getServerName(BankLetter $bankLetter): string
    {
        return $bankLetter->getBank()->getServerName() ?? '--';
    }

    /**
     * Get Signature name.
     *
     * @param SignatureBankLetter $signatureBankLetter
     *
     * @return string
     */
    protected function getSignatureName(SignatureBankLetter $signatureBankLetter): string
    {
        switch ($signatureBankLetter->getType()) {
            case SignatureBankLetter::TYPE_A:
                $signatureName = $this->translations['es_signature'];
                break;
            case SignatureBankLetter::TYPE_E:
                $signatureName = $this->translations['encryption_signature'];
                break;
            case SignatureBankLetter::TYPE_X:
                $signatureName = $this->translations['authentication_signature'];
                break;
            default:
                throw new LogicException('Signature type unexpectable.');
        }

        return $signatureName;
    }

    /**
     * Get certificate created at.
     *
     * @param SignatureBankLetter $signatureBankLetter
     *
     * @return string
     */
    protected function getCertificateCreatedAt(SignatureBankLetter $signatureBankLetter): string
    {
        $certificateCreatedAt = $signatureBankLetter->getCertificateCreatedAt();

        return !empty($certificateCreatedAt) ? $certificateCreatedAt->format('d/m/Y H:i:s') : '--';
    }

    /**
     * @param string $certificateContent
     *
     * @return string
     */
    protected function formatCertificateContent(string $certificateContent): string
    {
        return trim(
            str_replace(
                ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"],
                '',
                $certificateContent
            )
        );
    }
}
