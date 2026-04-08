<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Models\Keyring;
use LogicException;

/**
 * Keyring manager that stores keyrings in a PHP array (in-memory).
 *
 * This implementation is useful when you want to store the keyring data
 * in a database, session, or any other storage mechanism that can be
 * represented as a PHP array. The array is passed by reference to
 * `saveKeyring()` so it gets updated with the serialized keyring data.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class ArrayKeyringManager extends KeyringManager
{
    /**
     * Load a keyring from array data.
     *
     * @param array<mixed>|string $resource The array containing serialized keyring data (passed by reference)
     * @param string $passphrase The passphrase to decrypt private keys
     * @param string $defaultVersion Default EBICS version if no keyring exists (default: VERSION_25)
     *
     * @return Keyring The loaded or newly created keyring
     * @throws LogicException If $resource is not an array
     */
    public function loadKeyring($resource, string $passphrase, string $defaultVersion = Keyring::VERSION_25): Keyring
    {
        if (!is_array($resource)) {
            throw new LogicException('Expects array.');
        }
        if (!empty($resource)) {
            $keyring = $this->keyringFactory->createKeyringFromData($resource);
        } else {
            $keyring = $this->createKeyring($defaultVersion);
        }
        $keyring->setPassword($passphrase);

        return $keyring;
    }

    /**
     * Save a keyring to array data.
     *
     * Serializes the keyring and updates the $resource array with the keyring data.
     *
     * @param Keyring $keyring The keyring to save
     * @param array<mixed>|string $resource The array to update with serialized keyring data (passed by reference)
     *
     * @return void
     * @throws LogicException If $resource is not an array
     * @param-out array<mixed> $resource
     */
    public function saveKeyring(Keyring $keyring, &$resource): void
    {
        if (!is_array($resource)) {
            throw new LogicException('Expects array.');
        }
        $resource = $this->keyringFactory->buildDataFromKeyring($keyring);
    }
}
