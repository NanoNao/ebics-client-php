<?php

namespace EbicsApi\Ebics\Models\Crypt;

/**
 * RSA key pair bundle.
 *
 * Holds a public key, a private key, and the password used to encrypt
 * the private key. Used for key generation, storage, and password rotation.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final readonly class KeyPair
{
    public function __construct(
        private Key $publicKey,
        private Key $privateKey,
        private string $password
    ) {
    }

    public function getPublicKey(): Key
    {
        return $this->publicKey;
    }

    public function getPrivateKey(): Key
    {
        return $this->privateKey;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
