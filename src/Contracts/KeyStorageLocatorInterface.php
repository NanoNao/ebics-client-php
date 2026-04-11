<?php

namespace EbicsApi\Ebics\Contracts;

/**
 * Key Storage Locator interface.
 *
 * Responsible for finding and returning the appropriate {@see KeyStorageInterface}
 * implementation based on the provided value. This implements the locator
 * (or registry) pattern, centralizing access to multiple key storage backends.
 *
 * EBICS Protocol Context:
 * Different EBICS deployments may require different storage strategies:
 * file-based storage for single-server setups, database storage for
 * multi-tenant applications, or in-memory storage for testing. The
 * KeyStorageLocator determines the appropriate storage based on the
 * input value (e.g., a file path, a database connection string, or
 * a registered storage name).
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface KeyStorageLocatorInterface
{
    /**
     * Constant identifying string-type storage resources.
     */
    public const LOCATE_STRING = 'string';

    /**
     * Find the appropriate key storage based on the given value.
     *
     * The value is inspected to determine which storage backend to use.
     * For example, a file path might map to file-based storage, while
     * a registered name might map to a pre-configured storage instance.
     *
     * @param mixed|string $value The value to inspect for storage selection
     *
     * @return KeyStorageInterface The resolved key storage instance
     */
    public function locate($value): KeyStorageInterface;

    /**
     * Get a key storage instance by its registered key/name.
     *
     * Returns a previously registered storage instance identified by
     * the given key. Throws an exception if no storage is registered
     * for the given key.
     *
     * @param string $key The registered storage identifier
     *
     * @return KeyStorageInterface The registered key storage instance
     */
    public function get(string $key): KeyStorageInterface;
}
