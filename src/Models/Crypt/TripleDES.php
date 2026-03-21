<?php

namespace EbicsApi\Ebics\Models\Crypt;

use EbicsApi\Ebics\Contracts\Crypt\TripleDESInterface;
use LogicException;

/**
 * Pure-PHP implementation of Triple DES.
 *
 * Uses openssl. Operates in the EDE3 mode (encrypt-decrypt-encrypt).
 */
final class TripleDES implements TripleDESInterface
{
    private string $method = 'DES-EDE3-CBC';
    private string $key;
    private string $iv;

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function setIV(string $iv): void
    {
        $this->iv = $iv;
    }

    public function decrypt(string $ciphertext): string
    {
        $decrypted = openssl_decrypt(
            $ciphertext,
            $this->method,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );
        if ($decrypted === false) {
            throw new LogicException('Decryption failed.');
        }
        return $decrypted;
    }

    public function encrypt(string $plaintext): string
    {
        $encrypted = openssl_encrypt(
            $plaintext,
            $this->method,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );
        if ($encrypted === false) {
            throw new LogicException('Encryption failed.');
        }
        return $encrypted;
    }
}
