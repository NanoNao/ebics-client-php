<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Contracts\SignatureInterface;
use EbicsApi\Ebics\Factories\Crypt\AESFactory;
use EbicsApi\Ebics\Factories\Crypt\RSAFactory;
use EbicsApi\Ebics\Models\Buffer;
use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Crypt\KeyPair;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Services\CryptService;
use EbicsApi\Ebics\Services\RandomService;
use EbicsApi\Ebics\Tests\AbstractEbicsTestCase;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

/**
 * Comprehensive unit tests for CryptService.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @group crypt-services
 */
class CryptServiceTest extends AbstractEbicsTestCase
{
    private CryptService $cryptService;
    private RSAFactory $rsaFactory;
    private AESFactory $aesFactory;
    private RandomService $randomService;

    protected function setUp(): void
    {
        $this->rsaFactory = new RSAFactory();
        $this->aesFactory = new AESFactory();
        $this->randomService = new RandomService();
        $this->cryptService = new CryptService($this->rsaFactory, $this->aesFactory, $this->randomService);
    }

    /**
     * Test hash calculation with default SHA-256.
     */
    public function testHashWithDefaultAlgorithm(): void
    {
        $text = 'test data';
        $hash = $this->cryptService->hash($text);

        self::assertNotEmpty($hash);
        self::assertEquals(32, strlen($hash)); // SHA-256 binary output
    }

    /**
     * Test hash calculation with hex output.
     */
    public function testHashWithHexOutput(): void
    {
        $text = 'test data';
        $hash = $this->cryptService->hash($text, 'sha256', false);

        self::assertNotEmpty($hash);
        self::assertEquals(64, strlen($hash)); // SHA-256 hex output
        self::assertEquals(hash('sha256', $text), $hash);
    }

    /**
     * Test hash calculation with different algorithms.
     */
    public function testHashWithDifferentAlgorithms(): void
    {
        $text = 'test data';

        $sha1Hash = $this->cryptService->hash($text, 'sha1');
        self::assertEquals(20, strlen($sha1Hash)); // SHA-1 binary

        $sha512Hash = $this->cryptService->hash($text, 'sha512');
        self::assertEquals(64, strlen($sha512Hash)); // SHA-512 binary
    }

    /**
     * Test key pair generation.
     */
    public function testGenerateKeyPair(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);

        self::assertInstanceOf(KeyPair::class, $keyPair);
        self::assertObjectHasProperty('privateKey', $keyPair);
        self::assertObjectHasProperty('publicKey', $keyPair);
        self::assertObjectHasProperty('password', $keyPair);
        self::assertEquals($password, $keyPair->getPassword());
    }

    /**
     * Test key pair generation with custom parameters.
     */
    public function testGenerateKeyPairWithCustomParameters(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password, 'sha256', 2048);

        self::assertInstanceOf(KeyPair::class, $keyPair);
        self::assertInstanceOf(Key::class, $keyPair->getPrivateKey());
        self::assertInstanceOf(Key::class, $keyPair->getPublicKey());
    }

    /**
     * Test nonce generation.
     */
    public function testGenerateNonce(): void
    {
        $nonce1 = $this->cryptService->generateNonce();
        $nonce2 = $this->cryptService->generateNonce();

        self::assertEquals(32, strlen($nonce1));
        self::assertMatchesRegularExpression('/^[0-9A-F]{32}$/', $nonce1);
        // Nonces should be different (with very high probability)
        self::assertNotEquals($nonce1, $nonce2);
    }

    /**
     * Test transaction key generation.
     */
    public function testGenerateTransactionKey(): void
    {
        $key1 = $this->cryptService->generateTransactionKey();
        $key2 = $this->cryptService->generateTransactionKey();

        self::assertEquals(16, strlen($key1));
        // Keys should be different (with very high probability)
        self::assertNotEquals($key1, $key2);
    }

    /**
     * Test order ID generation format.
     */
    public function testGenerateOrderIdFormat(): void
    {
        $orderId = $this->cryptService->generateOrderId();

        self::assertEquals(4, strlen($orderId));
        // First character should be A-Z
        self::assertMatchesRegularExpression('/^[A-Z]/', $orderId);
        // All characters should be alphanumeric
        self::assertMatchesRegularExpression('/^[A-Z][0-9A-Z]{3}$/', $orderId);
    }

    /**
     * Test multiple order IDs are unique.
     */
    public function testGenerateOrderIdUniqueness(): void
    {
        $orderIds = [];
        for ($i = 0; $i < 100; $i++) {
            $orderIds[] = $this->cryptService->generateOrderId();
        }

        // Most order IDs should be unique (with very high probability)
        // Due to randomness, we expect at least 95% uniqueness
        $uniqueCount = count(array_unique($orderIds));
        self::assertGreaterThanOrEqual(95, $uniqueCount);
    }

    /**
     * Test binary to array conversion.
     */
    public function testBinToArray(): void
    {
        $binary = "\x41\x42\x43"; // ABC
        $array = $this->cryptService->binToArray($binary);

        self::assertIsArray($array);
        // unpack returns 1-indexed array
        self::assertEquals([1 => 65, 2 => 66, 3 => 67], $array);
    }

    /**
     * Test calculate key from exponent and modulus.
     */
    public function testCalculateKey(): void
    {
        $exponent = '00010001';
        $modulus = '00C4B1D2E3F4';

        $key = $this->cryptService->calculateKey($exponent, $modulus);

        self::assertEquals('10001 C4B1D2E3F4', $key);
    }

    /**
     * Test calculate key removes multiple leading zeros.
     */
    public function testCalculateKeyRemovesLeadingZeros(): void
    {
        $exponent = '000001';
        $modulus = '0000ABCD';

        $key = $this->cryptService->calculateKey($exponent, $modulus);

        self::assertEquals('1 ABCD', $key);
    }

    /**
     * Test calculate key with no leading zeros.
     */
    public function testCalculateKeyWithNoLeadingZeros(): void
    {
        $exponent = '1234';
        $modulus = 'ABCD';

        $key = $this->cryptService->calculateKey($exponent, $modulus);

        self::assertEquals('1234 ABCD', $key);
    }

    /**
     * Test encrypt and decrypt roundtrip with AES.
     */
    public function testEncryptDecryptRoundtrip(): void
    {
        $key = $this->cryptService->generateTransactionKey();
        $data = 'Sensitive data to encrypt';

        // Encrypt
        $encrypted = $this->cryptService->encryptByKey($key, $data);
        self::assertNotEmpty($encrypted);

        // Decrypt
        $tempFile = tempnam(sys_get_temp_dir(), 'ebics_test_');
        $encryptedBuffer = new Buffer($tempFile);
        $encryptedBuffer->open('w+');
        $encryptedBuffer->write($encrypted);
        $encryptedBuffer->rewind();

        $decryptedBuffer = new Buffer($tempFile . '_decrypted');
        $decryptedBuffer->open('w+');

        // Reset encrypted buffer position
        $encryptedBuffer->rewind();
        $this->cryptService->decryptByKey($key, $encryptedBuffer, $decryptedBuffer);

        $decryptedBuffer->rewind();
        $decrypted = $decryptedBuffer->readContent();

        self::assertEquals($data, $decrypted);

        // Cleanup
        $encryptedBuffer->close();
        $decryptedBuffer->close();
        @unlink($tempFile);
        @unlink($tempFile . '_decrypted');
    }

    /**
     * Test public key decomposition.
     */
    public function testDecomposePublicKey(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);

        $components = $this->cryptService->decomposePublicKey($keyPair->getPublicKey());

        self::assertIsArray($components);
        self::assertArrayHasKey('e', $components);
        self::assertArrayHasKey('m', $components);
        self::assertIsString($components['e']);
        self::assertIsString($components['m']);
        self::assertNotEmpty($components['e']);
        self::assertNotEmpty($components['m']);
    }

    /**
     * Test private key validation with correct password.
     */
    public function testCheckPrivateKeyWithCorrectPassword(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);

        $isValid = $this->cryptService->checkPrivateKey($keyPair->getPrivateKey(), $password);

        self::assertTrue($isValid);
    }

    /**
     * Test private key validation with wrong password.
     */
    public function testCheckPrivateKeyWithWrongPassword(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);

        $isValid = $this->cryptService->checkPrivateKey($keyPair->getPrivateKey(), 'wrongpassword');

        self::assertFalse($isValid);
    }

    /**
     * Test change private key password.
     */
    public function testChangePrivateKeyPassword(): void
    {
        $oldPassword = 'oldpassword';
        $newPassword = 'newpassword';

        $keyPair = $this->cryptService->generateKeyPair($oldPassword);

        // Change password
        $newKeyPair = $this->cryptService->changePrivateKeyPassword(
            $keyPair,
            $oldPassword,
            $newPassword
        );

        self::assertInstanceOf(KeyPair::class, $newKeyPair);

        // Old password should fail
        self::assertFalse($this->cryptService->checkPrivateKey($newKeyPair->getPrivateKey(), $oldPassword));

        // New password should work
        self::assertTrue($this->cryptService->checkPrivateKey($newKeyPair->getPrivateKey(), $newPassword));
    }

    /**
     * Test signing with A005 version.
     */
    public function testSignWithA005(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);
        $data = 'data to sign';

        $signature = $this->cryptService->sign(
            $keyPair->getPrivateKey(),
            $password,
            SignatureInterface::A_VERSION5,
            $data
        );

        self::assertNotEmpty($signature);
        self::assertIsString($signature);
    }

    /**
     * Test signing with A006 version.
     */
    public function testSignWithA006(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);
        $data = 'data to sign';

        $signature = $this->cryptService->sign(
            $keyPair->getPrivateKey(),
            $password,
            SignatureInterface::A_VERSION6,
            $data
        );

        self::assertNotEmpty($signature);
        self::assertIsString($signature);
    }

    /**
     * Test signing with unsupported version.
     */
    public function testSignWithUnsupportedVersion(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);
        $data = 'data to sign';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Algorithm type for Version A999 not supported');

        $this->cryptService->sign(
            $keyPair->getPrivateKey(),
            $password,
            'A999',
            $data
        );
    }

    /**
     * Test encrypt transaction key.
     */
    public function testEncryptTransactionKey(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);
        $transactionKey = $this->cryptService->generateTransactionKey();

        $encryptedKey = $this->cryptService->encryptTransactionKey(
            $keyPair->getPublicKey(),
            $transactionKey
        );

        self::assertNotEmpty($encryptedKey);
        self::assertIsString($encryptedKey);
        // Encrypted key should be different from original
        self::assertNotEquals($transactionKey, $encryptedKey);
    }

    /**
     * Test encrypt method with A005 version.
     */
    public function testEncryptWithA005(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);
        $data = 'data to encrypt';

        $encrypted = $this->cryptService->encrypt(
            $keyPair->getPrivateKey(),
            $password,
            SignatureInterface::A_VERSION5,
            $data
        );

        self::assertNotEmpty($encrypted);
        self::assertIsString($encrypted);
    }

    /**
     * Test encrypt method with A006 version.
     */
    public function testEncryptWithA006(): void
    {
        $password = 'testpassword';
        $keyPair = $this->cryptService->generateKeyPair($password);
        $data = 'data to encrypt';

        $encrypted = $this->cryptService->encrypt(
            $keyPair->getPrivateKey(),
            $password,
            SignatureInterface::A_VERSION6,
            $data
        );

        self::assertNotEmpty($encrypted);
        self::assertIsString($encrypted);
    }

    /**
     * Test certificate fingerprint calculation.
     */
    public function testCalculateCertificateFingerprint(): void
    {
        // Skip if openssl_x509_fingerprint doesn't work with test data
        // Using a self-signed certificate for testing
        $dn = [
            "countryName" => "US",
            "stateOrProvinceName" => "Test",
            "localityName" => "Test",
            "organizationName" => "Test",
            "commonName" => "test.example.com"
        ];

        $privkey = openssl_pkey_new();
        if ($privkey === false) {
            self::markTestSkipped('Cannot generate test certificate');
        }

        $csr = openssl_csr_new($dn, $privkey);
        if ($csr === false) {
            self::markTestSkipped('Cannot generate test certificate');
        }

        $cert = openssl_csr_sign($csr, null, $privkey, 365);
        if ($cert === false) {
            self::markTestSkipped('Cannot generate test certificate');
        }

        openssl_x509_export($cert, $certContent);

        $fingerprint = $this->cryptService->calculateCertificateFingerprint(
            $certContent,
            'sha256',
            true
        );

        self::assertNotEmpty($fingerprint);
        self::assertEquals(32, strlen($fingerprint)); // SHA-256 binary

        // Test hex output
        $fingerprintHex = $this->cryptService->calculateCertificateFingerprint(
            $certContent,
            'sha256',
            false
        );

        self::assertNotEmpty($fingerprintHex);
        self::assertEquals(64, strlen($fingerprintHex)); // SHA-256 hex
    }

    /**
     * Test certificate fingerprint with invalid certificate.
     */
    public function testCalculateCertificateFingerprintWithInvalidCert(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Can not calculate fingerprint for certificate.');

        // @ Suppress PHP warning from openssl_x509_fingerprint when given invalid input
        @$this->cryptService->calculateCertificateFingerprint(
            'invalid certificate content',
            'sha256',
            true
        );
    }

    /**
     * Test decrypt order data without signature E throws exception.
     */
    public function testDecryptOrderDataCompressedWithoutSignatureE(): void
    {
        $keyring = new Keyring(Keyring::VERSION_25);
        $keyring->setPassword('testpassword');

        $orderDataEncrypted = new Buffer(tempnam(sys_get_temp_dir(), 'ebics_test_'));
        $orderDataCompressed = new Buffer(tempnam(sys_get_temp_dir(), 'ebics_test_'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Signature E is not set.');

        $this->cryptService->decryptOrderDataCompressed(
            $keyring,
            $orderDataEncrypted,
            $orderDataCompressed,
            'encrypted_transaction_key'
        );
    }

    /**
     * Generate a self-signed test certificate in PEM format.
     */
    private function generateTestCertificate(): ?string
    {
        $dn = [
            "countryName" => "US",
            "stateOrProvinceName" => "Test",
            "localityName" => "Test",
            "organizationName" => "Test",
            "commonName" => "test.example.com"
        ];

        $privkey = openssl_pkey_new();
        if ($privkey === false) {
            return null;
        }

        $csr = openssl_csr_new($dn, $privkey);
        if ($csr === false) {
            return null;
        }

        $cert = openssl_csr_sign($csr, null, $privkey, 365);
        if ($cert === false) {
            return null;
        }

        openssl_x509_export($cert, $certContent);

        return $certContent;
    }

    /**
     * Provide PEM transformation functions to test normalization.
     */
    public static function malformedCertificateFormatsProvider(): array
    {
        return [
            'crlf_line_endings' => [
                static fn (string $pem): string => str_replace("\n", "\r\n", $pem),
            ],
            'cr_line_endings' => [
                static fn (string $pem): string => str_replace("\n", "\r", $pem),
            ],
            'empty_lines_between' => [
                static fn (string $pem): string => str_replace("\n", "\n\n", $pem),
            ],
            'leading_trailing_whitespace' => [
                static fn (string $pem): string => "   \n  " . $pem . "  \n   ",
            ],
        ];
    }

    /**
     * Test that calculateCertificateFingerprint normalizes PEM before fingerprinting.
     */
    #[DataProvider('malformedCertificateFormatsProvider')]
    #[Group('crypt-services')]
    public function testCalculateCertificateFingerprintNormalizesPem(callable $transform): void
    {
        $certContent = $this->generateTestCertificate();

        if ($certContent === null) {
            self::markTestSkipped('Cannot generate test certificate');
        }

        $malformedCert = $transform($certContent);

        self::assertNotEquals(
            $certContent,
            $malformedCert,
            'The transformation should produce a malformed certificate'
        );

        $expectedFingerprint = $this->cryptService->calculateCertificateFingerprint(
            $certContent,
            'sha256',
            false
        );

        $actualFingerprint = $this->cryptService->calculateCertificateFingerprint(
            $malformedCert,
            'sha256',
            false
        );

        self::assertEquals($expectedFingerprint, $actualFingerprint);
    }

    /**
     * Test that calculateCertificateFingerprint keeps DER input unchanged.
     */
    public function testCalculateCertificateFingerprintSupportsDerInput(): void
    {
        $certContent = $this->generateTestCertificate();

        if ($certContent === null) {
            self::markTestSkipped('Cannot generate test certificate');
        }

        $derContent = $this->convertPemToDer($certContent);
        self::assertNotEmpty($derContent);

        $expectedFingerprint = hash('sha256', $derContent);
        $actualFingerprint = $this->cryptService->calculateCertificateFingerprint(
            $derContent,
            'sha256',
            false
        );

        self::assertSame($expectedFingerprint, $actualFingerprint);
    }

    /**
     * Convert PEM certificate content to raw DER bytes.
     */
    private function convertPemToDer(string $certContent): string
    {
        $normalizedCert = preg_replace(
            '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\R/',
            '',
            $certContent
        );
        if ($normalizedCert === null) {
            self::fail('Cannot normalize PEM certificate.');
        }

        $derContent = base64_decode($normalizedCert, true);
        if ($derContent === false) {
            self::fail('Cannot decode PEM certificate.');
        }

        return $derContent;
    }
}
