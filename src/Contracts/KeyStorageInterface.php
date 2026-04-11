<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\Crypt\Key;

/**
 * Key Storage interface.
 *
 * Defines the contract for persistent storage of individual cryptographic
 * keys and certificates. Each storage instance manages a single key or
 * certificate, identified by a unique string key.
 *
 * EBICS Protocol Context:
 * The KeyStorageInterface provides low-level persistence for the raw
 * cryptographic materials used by EBICS:
 * - **Public keys**: User's own keys (generated) and bank's keys (received via HPB)
 * - **Private keys**: User's secret keys (encrypted with passphrase)
 * - **Certificates**: X.509 certificates for certified EBICS communication
 *
 * Implementations may store data in files, databases, memory, or any
 * other backend. The storage is keyed by identifier strings, allowing
 * multiple keys/certificates to coexist.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface KeyStorageInterface
{
    /**
     * Write a public key to storage.
     *
     * @param Key $key The public key to store
     *
     * @return string The identifier key under which the public key was stored
     */
    public function writePublicKey(Key $key): string;

    /**
     * Read a public key from storage.
     *
     * @param string $key The identifier of the public key to retrieve
     *
     * @return Key The stored public key
     */
    public function readPublicKey(string $key): Key;

    /**
     * Write a private key to storage.
     *
     * The private key should be encrypted before storage to protect
     * against unauthorized access.
     *
     * @param Key $key The private key to store
     *
     * @return string The identifier key under which the private key was stored
     */
    public function writePrivateKey(Key $key): string;

    /**
     * Read a private key from storage.
     *
     * @param string $key The identifier of the private key to retrieve
     *
     * @return Key The stored private key
     */
    public function readPrivateKey(string $key): Key;

    /**
     * Write an X.509 certificate to storage.
     *
     * @param string $certificate The PEM-encoded certificate content to store
     *
     * @return string The identifier key under which the certificate was stored
     */
    public function writeCertificate(string $certificate): string;

    /**
     * Read an X.509 certificate from storage.
     *
     * @param string $certificate The identifier of the certificate to retrieve
     *
     * @return string The PEM-encoded certificate content
     */
    public function readCertificate(string $certificate): string;
}
