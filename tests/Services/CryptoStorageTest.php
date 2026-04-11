<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Contracts\KeyStorageLocatorInterface;
use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Crypt\RSA;
use EbicsApi\Ebics\Services\CryptoStorage;
use EbicsApi\Ebics\Services\KeyStorageLocator;
use EbicsApi\Ebics\Services\StringKeyStorage;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CryptoStorage.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class CryptoStorageTest extends TestCase
{
    private CryptoStorage $cryptoStorage;
    private KeyStorageLocator $locator;

    protected function setUp(): void
    {
        $this->locator = new KeyStorageLocator();
        $this->cryptoStorage = new CryptoStorage($this->locator);
    }

    /**
     * Test write and read public key returns null for null input.
     */
    public function testWritePublicKeyReturnsNullForNullInput(): void
    {
        $result = $this->cryptoStorage->writePublicKey(null);
        self::assertNull($result);
    }

    /**
     * Test read public key returns null for null input.
     */
    public function testReadPublicKeyReturnsNullForNullInput(): void
    {
        $result = $this->cryptoStorage->readPublicKey(null);
        self::assertNull($result);
    }

    /**
     * Test write and read private key returns null for null input.
     */
    public function testWritePrivateKeyReturnsNullForNullInput(): void
    {
        $result = $this->cryptoStorage->writePrivateKey(null);
        self::assertNull($result);
    }

    /**
     * Test read private key returns null for null input.
     */
    public function testReadPrivateKeyReturnsNullForNullInput(): void
    {
        $result = $this->cryptoStorage->readPrivateKey(null);
        self::assertNull($result);
    }

    /**
     * Test write and read certificate returns null for null input.
     */
    public function testWriteCertificateReturnsNullForNullInput(): void
    {
        $result = $this->cryptoStorage->writeCertificate(null);
        self::assertNull($result);
    }

    /**
     * Test read certificate returns null for null input.
     */
    public function testReadCertificateReturnsNullForNullInput(): void
    {
        $result = $this->cryptoStorage->readCertificate(null);
        self::assertNull($result);
    }

    /**
     * Test write and read public key roundtrip.
     */
    public function testWriteAndReadPublicKeyRoundtrip(): void
    {
        $keyData = 'public_key_data';
        $key = new Key($keyData, RSA::PUBLIC_FORMAT_PKCS1);

        $encoded = $this->cryptoStorage->writePublicKey($key);
        self::assertIsString($encoded);
        self::assertNotEmpty($encoded);

        $decoded = $this->cryptoStorage->readPublicKey($encoded);
        self::assertInstanceOf(Key::class, $decoded);
        self::assertEquals($keyData, $decoded->getKey());
    }

    /**
     * Test write and read private key roundtrip.
     */
    public function testWriteAndReadPrivateKeyRoundtrip(): void
    {
        $keyData = 'private_key_data';
        $key = new Key($keyData, RSA::PRIVATE_FORMAT_PKCS1);

        $encoded = $this->cryptoStorage->writePrivateKey($key);
        self::assertIsString($encoded);

        $decoded = $this->cryptoStorage->readPrivateKey($encoded);
        self::assertInstanceOf(Key::class, $decoded);
        self::assertEquals($keyData, $decoded->getKey());
    }

    /**
     * Test write and read certificate roundtrip.
     */
    public function testWriteAndReadCertificateRoundtrip(): void
    {
        $certificate = '-----BEGIN CERTIFICATE-----
MIIBkTCB+wIJAKHBfpegPjMCMA0GCSqGSIb3DQEBCwUAMBExDzANBgNVBAMMBnRl
c3QxMB4XDTE5MDEwMTAwMDAwMFoXDTIwMDEwMTAwMDAwMFowETEPMA0GA1UEAwwG
dGVzdDEwXDANBgkqhkiG9w0BAQEFAANLADBIAkEApDmm1uB+U1rGQ2IYp94s+5qJ
-----END CERTIFICATE-----';

        $encoded = $this->cryptoStorage->writeCertificate($certificate);
        self::assertIsString($encoded);

        $decoded = $this->cryptoStorage->readCertificate($encoded);
        self::assertEquals($certificate, $decoded);
    }

    /**
     * Test with custom key storage locator.
     */
    public function testWithCustomKeyStorageLocator(): void
    {
        $customLocator = new KeyStorageLocator([
            KeyStorageLocatorInterface::LOCATE_STRING => new StringKeyStorage(),
        ]);

        $cryptoStorage = new CryptoStorage($customLocator);

        $keyData = 'custom_test_key';
        $key = new Key($keyData, RSA::PUBLIC_FORMAT_PKCS1);

        $encoded = $cryptoStorage->writePublicKey($key);
        $decoded = $cryptoStorage->readPublicKey($encoded);

        self::assertEquals($keyData, $decoded->getKey());
    }
}
