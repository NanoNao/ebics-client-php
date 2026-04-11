<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Contracts\KeyStorageInterface;
use EbicsApi\Ebics\Contracts\KeyStorageLocatorInterface;
use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Crypt\RSA;
use EbicsApi\Ebics\Services\KeyStorageLocator;
use EbicsApi\Ebics\Services\StringKeyStorage;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for KeyStorageLocator.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class KeyStorageLocatorTest extends TestCase
{
    /**
     * Test default constructor creates string storage mapping.
     */
    public function testDefaultConstructorCreatesStringMapping(): void
    {
        $locator = new KeyStorageLocator();

        $storage = $locator->get(KeyStorageLocatorInterface::LOCATE_STRING);
        self::assertInstanceOf(StringKeyStorage::class, $storage);
    }

    /**
     * Test locate returns storage for string type.
     */
    public function testLocateReturnsStorageForString(): void
    {
        $locator = new KeyStorageLocator();

        $storage = $locator->locate('test_string');
        self::assertInstanceOf(StringKeyStorage::class, $storage);
    }

    /**
     * Test locate returns storage based on object class.
     */
    public function testLocateReturnsStorageForObject(): void
    {
        $key = new Key('key_data', RSA::PUBLIC_FORMAT_PKCS1);
        $customStorage = new class implements KeyStorageInterface {
            public function writePublicKey(Key $key): string
            {
                return '';
            }
            public function readPublicKey(string $key): Key
            {
                return new Key('', 0);
            }
            public function writePrivateKey(Key $key): string
            {
                return '';
            }
            public function readPrivateKey(string $key): Key
            {
                return new Key('', 0);
            }
            public function writeCertificate(string $certificate): string
            {
                return '';
            }
            public function readCertificate(string $certificate): string
            {
                return '';
            }
        };

        $locateMap = [
            Key::class => $customStorage,
        ];

        $locator = new KeyStorageLocator($locateMap);
        $storage = $locator->locate($key);

        self::assertSame($customStorage, $storage);
    }

    /**
     * Test custom locateMap in constructor.
     */
    public function testCustomLocateMapInConstructor(): void
    {
        $customStorage = new StringKeyStorage();
        $locateMap = [
            'custom_type' => $customStorage,
        ];

        $locator = new KeyStorageLocator($locateMap);
        $storage = $locator->get('custom_type');

        self::assertSame($customStorage, $storage);
    }

    /**
     * Test get returns correct storage by key.
     */
    public function testGetReturnsCorrectStorageByKey(): void
    {
        $locator = new KeyStorageLocator();

        $stringStorage = $locator->get(KeyStorageLocatorInterface::LOCATE_STRING);
        self::assertInstanceOf(StringKeyStorage::class, $stringStorage);
    }

    /**
     * Test locate with different primitive types.
     */
    public function testLocateWithDifferentPrimitiveTypes(): void
    {
        $locator = new KeyStorageLocator();

        // String type
        $stringStorage = $locator->locate('test');
        self::assertInstanceOf(StringKeyStorage::class, $stringStorage);
    }

    /**
     * Test locate uses class name for objects.
     */
    public function testLocateUsesClassNameForObjects(): void
    {
        $key = new Key('data', RSA::PUBLIC_FORMAT_PKCS1);
        $keyStorage = new StringKeyStorage();

        $locateMap = [
            Key::class => $keyStorage,
        ];

        $locator = new KeyStorageLocator($locateMap);
        $storage = $locator->locate($key);

        self::assertSame($keyStorage, $storage);
    }
}
