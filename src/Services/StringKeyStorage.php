<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\KeyStorageInterface;
use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Crypt\RSA;

/**
 * Key storage implementation using base64-encoded strings.
 *
 * Stores cryptographic keys (public keys, private keys) and X.509 certificates
 * as base64-encoded strings. This is the default storage mechanism used by
 * the EBICS library for keyring serialization.
 *
 * Keys are encoded/decoded using base64 to ensure safe string representation
 * of binary cryptographic data. The encoding format is compatible with JSON
 * serialization used by FileKeyringManager and ArrayKeyringManager.
 *
 * Key formats:
 * - Public keys: PKCS#1 format, base64-encoded
 * - Private keys: PKCS#1 format, base64-encoded (should be password-encrypted before storage)
 * - Certificates: Raw PEM/DER content, base64-encoded
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class StringKeyStorage implements KeyStorageInterface
{
    /**
     * @inheritDoc
     */
    public function writePublicKey(Key $key): string
    {
        return base64_encode($key->getKey());
    }

    /**
     * @inheritDoc
     */
    public function readPublicKey(string $key): Key
    {
        return new Key(base64_decode($key), RSA::PUBLIC_FORMAT_PKCS1);
    }

    /**
     * @inheritDoc
     */
    public function writePrivateKey(Key $key): string
    {
        return base64_encode($key->getKey());
    }

    /**
     * @inheritDoc
     */
    public function readPrivateKey(string $key): Key
    {
        return new Key(base64_decode($key), RSA::PRIVATE_FORMAT_PKCS1);
    }

    /**
     * @inheritDoc
     */
    public function writeCertificate(string $certificate): string
    {
        return base64_encode($certificate);
    }

    /**
     * @inheritDoc
     */
    public function readCertificate(string $certificate): string
    {
        return base64_decode($certificate);
    }
}
