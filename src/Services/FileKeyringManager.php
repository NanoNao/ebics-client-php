<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Models\Keyring;
use LogicException;

/**
 * Keyring manager that stores keyrings as JSON files on disk.
 *
 * This implementation persists keyring data to JSON files at the specified
 * file path. It's the most common choice for production use where keyrings
 * need to persist between application runs.
 *
 * The keyring file is created automatically if it doesn't exist.
 * The file is formatted with JSON_PRETTY_PRINT for human readability.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class FileKeyringManager extends KeyringManager
{
    /**
     * Load a keyring from a JSON file.
     *
     * If the file doesn't exist or is empty, a new empty keyring is created.
     *
     * @param array<mixed>|string $resource Path to the JSON file containing the keyring data
     * @param string $passphrase The passphrase to decrypt private keys
     * @param string $defaultVersion Default EBICS version if no keyring file exists (default: VERSION_25)
     *
     * @return Keyring The loaded or newly created keyring
     * @throws LogicException If $resource is not a string
     */
    public function loadKeyring($resource, string $passphrase, string $defaultVersion = Keyring::VERSION_25): Keyring
    {
        if (!is_string($resource)) {
            throw new LogicException('Expects string.');
        }
        if (is_file($resource) && ($content = file_get_contents($resource)) !== false) {
            $keyring = $this->keyringFactory->createKeyringFromData(json_decode($content, true));
        } else {
            $keyring = $this->createKeyring($defaultVersion);
        }
        $keyring->setPassword($passphrase);

        return $keyring;
    }

    /**
     * Save a keyring to a JSON file.
     *
     * Serializes the keyring and writes it to the specified JSON file with
     * pretty printing enabled for human readability.
     *
     * @param Keyring $keyring The keyring to save
     * @param array<mixed>|string $resource Path to the JSON file to update (passed by reference)
     *
     * @return void
     * @throws LogicException If $resource is not a string
     * @param-out string $resource
     */
    public function saveKeyring(Keyring $keyring, &$resource): void
    {
        if (!is_string($resource)) {
            throw new LogicException('Expects string.');
        }
        $data = $this->keyringFactory->buildDataFromKeyring($keyring);
        file_put_contents($resource, json_encode($data, JSON_PRETTY_PRINT));
    }
}
