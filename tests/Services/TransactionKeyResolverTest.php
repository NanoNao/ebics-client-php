<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Models\Signature;
use EbicsApi\Ebics\Services\TransactionKeyResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TransactionKeyResolverTest extends TestCase
{
    private TransactionKeyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TransactionKeyResolver();
    }

    public function testResolveThrowsWhenSignatureENotSet(): void
    {
        $keyring = new Keyring(Keyring::VERSION_25);
        $keyring->setPassword('test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Signature E is not set.');

        $this->resolver->resolve($keyring, 'encrypted_key');
    }

    public function testResolveThrowsWhenPrivateKeyNotSet(): void
    {
        $keyring = new Keyring(Keyring::VERSION_25);
        $keyring->setPassword('test');

        $publicKey = new Key('-----BEGIN PUBLIC KEY-----', 1);
        $signature = new Signature('E', $publicKey, null);
        $keyring->setUserSignatureE($signature);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Signature E private key is not set.');

        $this->resolver->resolve($keyring, 'encrypted_key');
    }

    public function testResolveThrowsWhenPrivateKeyCannotBeLoaded(): void
    {
        $keyring = new Keyring(Keyring::VERSION_25);
        $keyring->setPassword('wrong_password');

        $privateKey = new Key('not-a-real-pem-key', 1);
        $publicKey = new Key('-----BEGIN PUBLIC KEY-----', 1);
        $signature = new Signature('E', $publicKey, $privateKey);
        $keyring->setUserSignatureE($signature);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot load private key.');

        $this->resolver->resolve($keyring, 'encrypted_key');
    }

    public function testResolveThrowsOnDecryptionFailure(): void
    {
        $password = 'test_password';
        $keyring = new Keyring(Keyring::VERSION_25);
        $keyring->setPassword($password);

        $aes = new \EbicsApi\Ebics\Services\Processor\AESEncryptor(new TransactionKeyResolver());
        $rsaFactory = new \EbicsApi\Ebics\Factories\Crypt\RSAFactory($aes);
        $rsa = $rsaFactory->create(\EbicsApi\Ebics\Models\Crypt\RSA::PRIVATE_FORMAT_PKCS1);
        $rsa->setPassword($password);
        $keyPair = $rsa->createKey(2048);

        $signature = new Signature('E', $keyPair->getPublicKey(), $keyPair->getPrivateKey());
        $keyring->setUserSignatureE($signature);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to RSA-decrypt transaction key');

        $this->resolver->resolve($keyring, '');
    }

    public function testResolveSuccessfullyDecryptsTransactionKey(): void
    {
        $password = 'test_password';
        $aesKey = '1234567890123456';
        $keyring = new Keyring(Keyring::VERSION_25);
        $keyring->setPassword($password);

        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $privateKeyPem = '';
        openssl_pkey_export($keyPair, $privateKeyPem, $password);

        $details = openssl_pkey_get_details($keyPair);
        $publicKeyPem = $details['key'];

        $publicKey = openssl_pkey_get_public($publicKeyPem);
        $encryptedKey = '';
        openssl_public_encrypt($aesKey, $encryptedKey, $publicKey, OPENSSL_PKCS1_PADDING);

        $privateKey = new Key($privateKeyPem, 1);
        $publicKeyObj = new Key($publicKeyPem, 1);
        $signature = new Signature('E', $publicKeyObj, $privateKey);
        $keyring->setUserSignatureE($signature);

        $decrypted = $this->resolver->resolve($keyring, $encryptedKey);

        self::assertEquals($aesKey, $decrypted);
    }
}
