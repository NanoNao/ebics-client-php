<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\KeyringManagerInterface;
use EbicsApi\Ebics\Factories\Crypt\RSAFactory;
use EbicsApi\Ebics\Factories\KeyringFactory;
use EbicsApi\Ebics\Factories\SignatureFactory;
use EbicsApi\Ebics\Models\Keyring;

/**
 * Abstract base class for managing EBICS keyring lifecycle.
 *
 * A keyring holds sets of private user keys and public bank keys for EBICS
 * communication. Private user keys are always AES-encrypted using the
 * specified passphrase (derived via PBKDF2).
 *
 * The keyring manages three types of signatures:
 * - **Signature A** (Authentication): Used to sign orders sent to the bank
 * - **Signature X** (Signing/Transaction): Used for transaction signing (X002)
 * - **Signature E** (Encryption): Used to encrypt/decrypt order data (E002)
 *
 * Concrete implementations determine where the keyring is persisted:
 * - `FileKeyringManager`: Stores keyring as a JSON file on disk
 * - `ArrayKeyringManager`: Stores keyring in a PHP array (in-memory)
 *
 * Each resource (file path or array reference) maintains a singleton
 * keyring instance for consistency.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class KeyringManager implements KeyringManagerInterface
{
    protected KeyringFactory $keyringFactory;

    /**
     * Constructor.
     *
     * @param KeyringFactory|null $keyringFactory Factory for creating keyring instances.
     *                                            If null, a default factory is created with
     *                                            RSA signature support and string-based key storage.
     */
    public function __construct(?KeyringFactory $keyringFactory = null)
    {
        $this->keyringFactory = $keyringFactory ?? new KeyringFactory(
            new SignatureFactory(new RSAFactory()),
            new CryptoStorage(new KeyStorageLocator())
        );
    }

    /**
     * Create a new empty keyring for the specified EBICS version.
     *
     * @param string $version EBICS protocol version (e.g., Keyring::VERSION_24, VERSION_25, VERSION_30)
     *
     * @return Keyring A new empty keyring instance
     */
    public function createKeyring(string $version): Keyring
    {
        return new Keyring($version);
    }
}
