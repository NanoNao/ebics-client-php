<?php

namespace EbicsApi\Ebics\Contracts\Processor;

/**
 * AES-128-CBC encrypt/decrypt pipe interface.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface AESEncryptorInterface
{
    /**
     * Encrypt data with AES-128-CBC.
     *
     * @param array<string, mixed> $context Context keys: transactionKey (string)
     */
    public function encrypt(string $data, array $context = []): string;

    /**
     * Decrypt data with AES-128-CBC.
     *
     * @param array<string, mixed> $context Context keys: transactionKey (string), keyring (Keyring)
     */
    public function decrypt(string $data, array $context = []): string;
}
