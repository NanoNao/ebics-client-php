<?php

namespace EbicsApi\Ebics\Tests\Services\Processor;

use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Models\Signature;
use EbicsApi\Ebics\Services\Processor\AESEncryptor;
use EbicsApi\Ebics\Services\TransactionKeyResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AESEncryptorTest extends TestCase
{
    private AESEncryptor $aesEncryptor;

    protected function setUp(): void
    {
        $this->aesEncryptor = new AESEncryptor(new TransactionKeyResolver());
    }

    public function testEncryptDecryptRoundtrip(): void
    {
        $aesKey = '1234567890123456';
        $data = 'Hello World! This is test data for AES encryption.';

        $encrypted = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);

        self::assertNotEquals($data, $encrypted);

        [$keyring, $rsaEncryptedKey] = $this->createKeyringWithKey($aesKey);

        $decrypted = $this->aesEncryptor->decrypt($encrypted, [
            'transactionKey' => $rsaEncryptedKey,
            'keyring' => $keyring,
        ]);

        self::assertEquals($data, $decrypted);
    }

    public function testEncryptDecryptEmptyString(): void
    {
        $aesKey = '1234567890123456';
        $data = '';

        $encrypted = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);

        [$keyring, $rsaEncryptedKey] = $this->createKeyringWithKey($aesKey);

        $decrypted = $this->aesEncryptor->decrypt($encrypted, [
            'transactionKey' => $rsaEncryptedKey,
            'keyring' => $keyring,
        ]);

        self::assertEquals($data, $decrypted);
    }

    public function testEncryptDecryptBlockSizeData(): void
    {
        $aesKey = '1234567890123456';
        $data = '1234567890123456';

        $encrypted = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);

        [$keyring, $rsaEncryptedKey] = $this->createKeyringWithKey($aesKey);

        $decrypted = $this->aesEncryptor->decrypt($encrypted, [
            'transactionKey' => $rsaEncryptedKey,
            'keyring' => $keyring,
        ]);

        self::assertEquals($data, $decrypted);
    }

    public function testEncryptDecryptBinaryData(): void
    {
        $aesKey = '1234567890123456';
        $data = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\xff\xfe\xfd";

        $encrypted = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);

        [$keyring, $rsaEncryptedKey] = $this->createKeyringWithKey($aesKey);

        $decrypted = $this->aesEncryptor->decrypt($encrypted, [
            'transactionKey' => $rsaEncryptedKey,
            'keyring' => $keyring,
        ]);

        self::assertEquals($data, $decrypted);
    }

    public function testEncryptProducesBlockAlignedOutput(): void
    {
        $aesKey = '1234567890123456';
        $data = 'Short';

        $encrypted = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);

        self::assertEquals(0, strlen($encrypted) % 16);
    }

    public function testEncryptProducesConsistentOutput(): void
    {
        $aesKey = '1234567890123456';
        $data = 'Test data';

        $encrypted1 = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);
        $encrypted2 = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);

        self::assertEquals($encrypted1, $encrypted2);
    }

    public function testDecryptWithRawKey(): void
    {
        $aesKey = '1234567890123456';
        $data = 'Decrypt with raw key directly.';

        $encrypted = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);

        $decrypted = $this->aesEncryptor->decrypt($encrypted, [
            'key' => $aesKey,
        ]);

        self::assertEquals($data, $decrypted);
    }

    public function testDecryptWithRawKeyBinaryData(): void
    {
        $aesKey = '1234567890123456';
        $data = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\xff\xfe\xfd";

        $encrypted = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);

        $decrypted = $this->aesEncryptor->decrypt($encrypted, [
            'key' => $aesKey,
        ]);

        self::assertEquals($data, $decrypted);
    }

    public function testEncryptDecryptRoundtripWithRawKey(): void
    {
        $aesKey = '1234567890123456';
        $data = 'Full roundtrip using raw key for both encrypt and decrypt.';

        $encrypted = $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);

        $decrypted = $this->aesEncryptor->decrypt($encrypted, [
            'key' => $aesKey,
        ]);

        self::assertEquals($data, $decrypted);
    }

    /**
     * @return array{Keyring, string}
     */
    private function createKeyringWithKey(string $aesKey): array
    {
        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $privateKeyPem = '';
        $password = 'test_password';
        openssl_pkey_export($keyPair, $privateKeyPem, $password);

        $details = openssl_pkey_get_details($keyPair);
        $publicKeyPem = $details['key'];

        $publicKey = openssl_pkey_get_public($publicKeyPem);
        $rsaEncryptedKey = '';
        openssl_public_encrypt($aesKey, $rsaEncryptedKey, $publicKey, OPENSSL_PKCS1_PADDING);

        $privateKey = new Key($privateKeyPem, 1);
        $publicKey = new Key($publicKeyPem, 1);
        $signature = new Signature('E', $publicKey, $privateKey);

        $keyring = new Keyring(Keyring::VERSION_25);
        $keyring->setPassword($password);
        $keyring->setUserSignatureE($signature);

        return [$keyring, $rsaEncryptedKey];
    }

    public function testEncryptExceedsMaxDataLength(): void
    {
        $aesKey = '1234567890123456';
        $data = str_repeat('A', 10485761);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data exceeds maximum length of');

        $this->aesEncryptor->encrypt($data, [
            'transactionKey' => $aesKey,
        ]);
    }

    public function testDecryptExceedsMaxDataLength(): void
    {
        $data = str_repeat('A', 10485761);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data exceeds maximum length of');

        $this->aesEncryptor->decrypt($data, [
            'key' => '1234567890123456',
        ]);
    }
}
