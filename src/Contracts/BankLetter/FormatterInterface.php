<?php

namespace EbicsApi\Ebics\Contracts\BankLetter;

use EbicsApi\Ebics\Models\BankLetter;

/**
 * Bank Letter Formatter interface.
 *
 * Defines the contract for formatting {@see BankLetter} objects into
 * human-readable output (e.g., plain text, HTML, PDF-ready markup).
 *
 * EBICS Protocol Context:
 * A bank letter (also called an "EBICS initialization letter") is a
 * document that contains the cryptographic parameters of an EBICS
 * setup, including:
 * - Hash values of user signatures (A, E, X) for verification
 * - Bank identification details (host ID, URL, country)
 * - User identification (partner ID, user ID)
 * - Public key fingerprints
 *
 * The bank letter is typically printed and sent by postal mail or
 * secure fax to the bank as part of the INI/HIA initialization
 * process, allowing the bank to verify the hash values before
 * activating the EBICS connection.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface FormatterInterface
{
    /**
     * Format a bank letter into a printable string representation.
     *
     * Transforms the BankLetter model into a human-readable format
     * suitable for printing or display. The output format depends on
     * the specific formatter implementation (text, HTML, etc.).
     *
     * @param BankLetter $bankLetter The bank letter model to format
     *
     * @return string The formatted bank letter ready for printing or display
     */
    public function format(BankLetter $bankLetter): string;

    /**
     * Set custom translations for the formatted output.
     *
     * Allows overriding the default labels/headers in the bank letter
     * with locale-specific translations. Translation keys are implementation-
     * dependent.
     *
     * @param array<string, string> $translations Associative array mapping
 *                                            translation keys to translated strings
     *
     * @return void
     */
    public function setTranslations(array $translations): void;
}
