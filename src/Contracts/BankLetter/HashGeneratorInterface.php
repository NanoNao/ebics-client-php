<?php

namespace EbicsApi\Ebics\Contracts\BankLetter;

use EbicsApi\Ebics\Contracts\SignatureInterface;

/**
 * Bank Letter Hash Generator interface.
 *
 * Generates cryptographic hash values for signature keys to be
 * included in the {@see \EbicsApi\Ebics\Models\BankLetter} for
 * bank verification.
 *
 * EBICS Protocol Context:
 * During the EBICS initialization process (INI/HIA/H3K), the client
 * generates public/private key pairs and sends the public keys to
 * the bank. The bank letter contains hash values (fingerprints) of
 * these public keys, which the bank uses to verify that the keys
 * received electronically match the keys documented in the printed
 * letter.
 *
 * This interface uses the Strategy pattern, allowing different hash
 * algorithms (SHA-256, SHA-512, etc.) to be plugged in depending
 * on the signature version and bank requirements.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface HashGeneratorInterface
{
    /**
     * Generate a hash (fingerprint) for the given signature's public key.
     *
     * Computes a cryptographic hash of the public key associated with
     * the provided signature. This hash is included in the bank letter
     * for manual verification by the bank.
     *
     * @param SignatureInterface $signature The signature whose public key
 *                                      hash should be generated
     *
     * @return string The hexadecimal hash string (fingerprint)
     */
    public function generate(SignatureInterface $signature): string;
}
