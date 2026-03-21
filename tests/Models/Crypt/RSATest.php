<?php

namespace EbicsApi\Ebics\Tests\Models\Crypt;

use EbicsApi\Ebics\Models\Crypt\RSA;
use PHPUnit\Framework\TestCase;

/**
 * Class RSATest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
class RSATest extends TestCase
{
    public function testCreateKeyAndSigningVerification(): void
    {
        $rsa = new RSA();
        $rsa->setPassword('secret');
        $keyPair = $rsa->createKey(1024);

        $this->assertNotEmpty($keyPair->getPrivateKey());
        $this->assertNotEmpty($keyPair->getPublicKey());

        $rsa->loadKey($keyPair->getPrivateKey()->getKey());
        $message = 'Hello World';

        $rsa->setHash('sha256');
        $rsa->setMGFHash('sha256');
        $signature = $rsa->sign($message);

        $this->assertNotEmpty($signature);

        $rsaVerify = new RSA();
        $rsaVerify->loadKey($keyPair->getPublicKey()->getKey());
        $rsaVerify->setHash('sha256');
        $rsaVerify->setMGFHash('sha256');

        $this->assertTrue($rsaVerify->verify($message, $signature));
    }

    public function testEncryptDecrypt(): void
    {
        $rsa = new RSA();
        $rsa->setPassword('secret');
        $keyPair = $rsa->createKey(1024);

        $plaintext = 'Secret Message';

        $rsaPub = new RSA();
        $rsaPub->loadKey($keyPair->getPublicKey()->getKey());
        $ciphertext = $rsaPub->encrypt($plaintext);

        $this->assertNotEquals($plaintext, $ciphertext);

        $rsaPriv = new RSA();
        $rsaPriv->setPassword('secret');
        $rsaPriv->loadKey($keyPair->getPrivateKey()->getKey());
        $decrypted = $rsaPriv->decrypt($ciphertext);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testPasswordProtectedKey(): void
    {
        $rsa = new RSA();
        $password = 'secret';
        $rsa->setPassword($password);
        $keyPair = $rsa->createKey(1024);

        $privateKey = $keyPair->getPrivateKey()->getKey();
        $this->assertStringContainsString('ENCRYPTED', $privateKey);

        $rsaSuccess = new RSA();
        $rsaSuccess->setPassword($password);
        $result = $rsaSuccess->loadKey($privateKey);
        $this->assertTrue($result);

        $rsaSuccess->setHash('sha256');
        $rsaSuccess->setMGFHash('sha256');

        $plaintext = 'test';
        $signature = $rsaSuccess->sign($plaintext);
        $this->assertNotEmpty($signature);
    }

    public function testChangePassword(): void
    {
        $rsa = new RSA();
        $oldPassword = 'old';
        $newPassword = 'new';
        $rsa->setPassword($oldPassword);

        $keyPair = $rsa->createKey(1024);

        $newKeyPair = $rsa->changePassword($keyPair, $oldPassword, $newPassword);

        $newPrivateKey = $newKeyPair->getPrivateKey()->getKey();

        $rsaCheck = new RSA();
        $rsaCheck->setPassword($newPassword);
        $this->assertTrue($rsaCheck->loadKey($newPrivateKey));
    }
}
