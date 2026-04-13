<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\KeyStorageLocatorInterface;
use EbicsApi\Ebics\Models\Crypt\Key;

/**
 * High-level key storage facade for EBICS cryptographic keys.
 *
 * Provides a convenient interface for reading and writing cryptographic keys
 * (public keys, private keys, X.509 certificates) to various storage backends.
 * The actual storage mechanism (file system, database, memory, etc.) is
 * determined by the `KeyStorageLocatorInterface` implementation.
 *
 * This class acts as a facade that delegates to the appropriate storage
 * implementation based on the key type, making it easy to swap storage
 * backends without changing client code.
 *
 * Supported storage types (registered via KeyStorageLocator):
 * - `StringKeyStorage`: Stores keys as base64-encoded strings (default)
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class CryptoStorage
{
    /**
     * Constructor.
     *
     * @param KeyStorageLocatorInterface $keyStorageLocator The locator for finding storage implementations
     */
    public function __construct(private readonly KeyStorageLocatorInterface $keyStorageLocator)
    {
    }

    /**
     * Write a public key to storage and return its encoded representation.
     *
     * @param Key|null $key The public key to store
     * @return string|null Base64-encoded key string, or null if $key is null
     */
    public function writePublicKey(?Key $key): ?string
    {
        return $key ? $this->keyStorageLocator->locate($key->getKey())->writePublicKey($key) : null;
    }

    /**
     * Read a public key from storage by its encoded representation.
     *
     * @param string|null $key The encoded key string (e.g., base64)
     * @return Key|null The decoded Key object, or null if $key is null
     */
    public function readPublicKey(?string $key): ?Key
    {
        return $key ? $this->keyStorageLocator->locate($key)->readPublicKey($key) : null;
    }

    /**
     * Write a private key to storage and return its encoded representation.
     *
     * @param Key|null $key The private key to store (should be password-encrypted)
     * @return string|null Base64-encoded key string, or null if $key is null
     */
    public function writePrivateKey(?Key $key): ?string
    {
        return $key ? $this->keyStorageLocator->locate($key->getKey())->writePrivateKey($key) : null;
    }

    /**
     * Read a private key from storage by its encoded representation.
     *
     * @param string|null $key The encoded key string (e.g., base64)
     * @return Key|null The decoded Key object, or null if $key is null
     */
    public function readPrivateKey(?string $key): ?Key
    {
        return $key ? $this->keyStorageLocator->locate($key)->readPrivateKey($key) : null;
    }

    /**
     * Write an X.509 certificate to storage and return its encoded representation.
     *
     * @param string|null $certificate The PEM or DER encoded certificate
     * @return string|null Encoded certificate string, or null if $certificate is null
     */
    public function writeCertificate(?string $certificate): ?string
    {
        return $certificate ? $this->keyStorageLocator->locate($certificate)->writeCertificate($certificate) : null;
    }

    /**
     * Read an X.509 certificate from storage by its encoded representation.
     *
     * @param string|null $certificate The encoded certificate string
     * @return string|null The decoded certificate (PEM format), or null if $certificate is null
     */
    public function readCertificate(?string $certificate): ?string
    {
        return $certificate ? $this->keyStorageLocator->locate($certificate)->readCertificate($certificate) : null;
    }
}
