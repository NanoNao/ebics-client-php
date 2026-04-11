<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\Keyring;

/**
 * EBICS Keyring Manager interface.
 *
 * Responsible for loading and persisting {@see Keyring} objects that contain
 * cryptographic keys, certificates, and signature data required for EBICS
 * communication.
 *
 * The KeyringManager abstracts the storage mechanism, allowing keyrings to be
 * saved to and loaded from various backends (files, databases, memory, etc.).
 * It supports both file-path-based and array-based resource configurations.
 *
 * EBICS Protocol Context:
 * The keyring is the central repository for all cryptographic materials:
 * - User signatures (A, E, X) — generated during initialization
 * - Bank public keys — received via HPB order
 * - X.509 certificates — for certified communication
 * - Transaction keys — for encrypting order data
 *
 * The keyring must be persisted between sessions to maintain continuity
 * of cryptographic identity across EBICS sessions.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface KeyringManagerInterface
{
    /**
     * Load a Keyring from storage or create a new one if none exists.
     *
     * The resource parameter determines where the keyring data is stored.
     * It can be either a file path (string) or an associative array containing
     * key data directly. The passphrase is used to decrypt the private keys
     * stored in the keyring.
     *
     * @param array<mixed>|string $resource Storage resource — either a file path
     *                                      or an array containing key data
     * @param string $passphrase Passphrase to decrypt the keyring's private keys
     * @param string $defaultVersion Default EBICS version for the keyring
     *                               (defaults to VERSION_25)
     *
     * @return Keyring The loaded or newly created keyring instance
     */
    public function loadKeyring($resource, string $passphrase, string $defaultVersion = Keyring::VERSION_25): Keyring;

    /**
     * Persist a Keyring to the specified storage resource.
     *
     * Serializes the keyring (including encrypted private keys) and writes it
     * to the storage backend identified by the resource parameter. The resource
     * is passed by reference so that the implementation can update it (e.g.,
     * set a file path after saving).
     *
     * @param Keyring $keyring The keyring instance to persist
     * @param array<mixed>|string $resource Storage resource — will be updated
     *                                      by reference with the storage location
     *
     * @return void
     */
    public function saveKeyring(Keyring $keyring, &$resource): void;
}
