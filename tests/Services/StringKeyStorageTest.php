<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Crypt\RSA;
use EbicsApi\Ebics\Services\StringKeyStorage;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StringKeyStorage.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class StringKeyStorageTest extends TestCase
{
    private StringKeyStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new StringKeyStorage();
    }

    /**
     * Test write and read public key roundtrip.
     */
    public function testWriteAndReadPublicKey(): void
    {
        $keyData = 'test_public_key_data';
        $key = new Key($keyData, RSA::PUBLIC_FORMAT_PKCS1);

        $encoded = $this->storage->writePublicKey($key);
        self::assertIsString($encoded);
        self::assertNotEmpty($encoded);

        $decoded = $this->storage->readPublicKey($encoded);
        self::assertInstanceOf(Key::class, $decoded);
        self::assertEquals($keyData, $decoded->getKey());
        self::assertEquals(RSA::PUBLIC_FORMAT_PKCS1, $decoded->getType());
    }

    /**
     * Test write and read private key roundtrip.
     */
    public function testWriteAndReadPrivateKey(): void
    {
        $keyData = 'test_private_key_data';
        $key = new Key($keyData, RSA::PRIVATE_FORMAT_PKCS1);

        $encoded = $this->storage->writePrivateKey($key);
        self::assertIsString($encoded);
        self::assertNotEmpty($encoded);

        $decoded = $this->storage->readPrivateKey($encoded);
        self::assertInstanceOf(Key::class, $decoded);
        self::assertEquals($keyData, $decoded->getKey());
        self::assertEquals(RSA::PRIVATE_FORMAT_PKCS1, $decoded->getType());
    }

    /**
     * Test write and read certificate roundtrip.
     */
    public function testWriteAndReadCertificate(): void
    {
        $certificate = '-----BEGIN CERTIFICATE-----
MIIBkTCB+wIJAKHBfpegPjMCMA0GCSqGSIb3DQEBCwUAMBExDzANBgNVBAMMBnRl
c3QxMB4XDTE5MDEwMTAwMDAwMFoXDTIwMDEwMTAwMDAwMFowETEPMA0GA1UEAwwG
dGVzdDEwXDANBgkqhkiG9w0BAQEFAANLADBIAkEApDmm1uB+U1rGQ2IYp94s+5qJ
-----END CERTIFICATE-----';

        $encoded = $this->storage->writeCertificate($certificate);
        self::assertIsString($encoded);
        self::assertNotEmpty($encoded);

        $decoded = $this->storage->readCertificate($encoded);
        self::assertEquals($certificate, $decoded);
    }

    /**
     * Test encoding is base64 for keys.
     */
    public function testPublicKeyIsBase64Encoded(): void
    {
        $keyData = 'raw_key_data';
        $key = new Key($keyData, RSA::PUBLIC_FORMAT_PKCS1);

        $encoded = $this->storage->writePublicKey($key);

        // Should be valid base64
        self::assertEquals(base64_encode($keyData), $encoded);
    }

    /**
     * Test certificate encoding is base64.
     */
    public function testCertificateIsBase64Encoded(): void
    {
        $certificate = 'certificate_content';

        $encoded = $this->storage->writeCertificate($certificate);

        self::assertEquals(base64_encode($certificate), $encoded);
    }

    /**
     * Test public key with binary data.
     */
    public function testPublicKeyWithBinaryData(): void
    {
        $keyData = "\x00\x01\x02\x03\x04\x05";
        $key = new Key($keyData, RSA::PUBLIC_FORMAT_PKCS1);

        $encoded = $this->storage->writePublicKey($key);
        $decoded = $this->storage->readPublicKey($encoded);

        self::assertEquals($keyData, $decoded->getKey());
    }

    /**
     * Test private key with binary data.
     */
    public function testPrivateKeyWithBinaryData(): void
    {
        $keyData = "\xFF\xFE\xFD\xFC\xFB\xFA";
        $key = new Key($keyData, RSA::PRIVATE_FORMAT_PKCS1);

        $encoded = $this->storage->writePrivateKey($key);
        $decoded = $this->storage->readPrivateKey($encoded);

        self::assertEquals($keyData, $decoded->getKey());
    }

    /**
     * Test empty certificate handling.
     */
    public function testEmptyCertificateHandling(): void
    {
        $certificate = '';

        $encoded = $this->storage->writeCertificate($certificate);
        $decoded = $this->storage->readCertificate($encoded);

        self::assertEquals('', $decoded);
    }
}
