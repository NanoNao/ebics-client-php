<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\KeyStorageInterface;
use EbicsApi\Ebics\Contracts\KeyStorageLocatorInterface;

/**
 * Locator service for finding key storage implementations.
 *
 * Maps key types (represented by class names or PHP types) to their
 * corresponding storage implementations. This allows the CryptoStorage
 * facade to delegate to the appropriate storage backend based on the
 * key being stored or retrieved.
 *
 * By default, it registers `StringKeyStorage` for string-based key storage,
 * which encodes keys as base64 strings. Custom storage implementations
 * can be registered by passing a custom locateMap in the constructor.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class KeyStorageLocator implements KeyStorageLocatorInterface
{
    /**
     * Map of key types to storage implementations.
     *
     * @var array<string, KeyStorageInterface>
     */
    private array $locateMap;

    /**
     * Constructor.
     *
     * @param array<string, KeyStorageInterface>|null $locateMap Custom mapping of key types to storage.
     *                                                          If null, uses default mapping with StringKeyStorage.
     */
    public function __construct(?array $locateMap = null)
    {
        $this->locateMap = $locateMap ?? [
            KeyStorageLocatorInterface::LOCATE_STRING => new StringKeyStorage(),
        ];
    }

    public function locate($value): KeyStorageInterface
    {
        $type = is_object($value) ? get_class($value) : gettype($value);

        return $this->locateMap[$type];
    }

    public function get(string $key): KeyStorageInterface
    {
        return $this->locateMap[$key];
    }
}
