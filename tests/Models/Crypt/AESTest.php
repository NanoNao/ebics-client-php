<?php

namespace EbicsApi\Ebics\Tests\Models\Crypt;

use EbicsApi\Ebics\Contracts\BufferInterface;
use EbicsApi\Ebics\Models\Crypt\AES;
use EbicsApi\Ebics\Tests\AbstractEbicsTestCase;

/**
 * Class AESTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
class AESTest extends AbstractEbicsTestCase
{
    public function testEncryptionAndDecryption(): void
    {
        $aes = new AES();
        $key = '1234567890123456';
        $iv = '1234567890123456';
        $plaintext = 'Hello World!';

        $aes->setKey($key);
        $aes->setIV($iv);

        $ciphertext = $aes->encrypt($plaintext);
        $decrypted = $aes->decrypt($ciphertext);

        $this->assertEquals($plaintext, $decrypted);
        $this->assertNotEquals($plaintext, $ciphertext);
    }

    public function testEncryptionAndDecryptionWithPadding(): void
    {
        $aes = new AES();
        $key = '1234567890123456';
        $iv = '1234567890123456';
        $plaintext = 'Hello World!!';

        $aes->setKey($key);
        $aes->setIV($iv);

        $ciphertext = $aes->encrypt($plaintext);
        $decrypted = $aes->decrypt($ciphertext);

        $this->assertEquals($plaintext, $decrypted);
        $this->assertEquals(16, strlen($ciphertext));
    }

    public function testSetKeyLength(): void
    {
        $aes = new AES();
        $aes->setKeyLength(128);
        $aes->setKey('1234567890123456');

        $this->addToAssertionCount(1);
    }

    public function testSetIV(): void
    {
        $aes = new AES();
        $key = '1234567890123456';
        $plaintext = 'Hello World!';

        $aes->setKey($key);

        $aes->setIV('1234567890123456');
        $ciphertext1 = $aes->encrypt($plaintext);

        $aes->setIV('6543210987654321');
        $ciphertext2 = $aes->encrypt($plaintext);

        $this->assertNotEquals($ciphertext1, $ciphertext2);
    }

    public function testDecryptBuffer(): void
    {
        $aes = new AES();
        $key = '1234567890123456';
        $iv = '1234567890123456';
        $plaintext = 'Hello World!';

        $aes->setKey($key);
        $aes->setIV($iv);
        $ciphertext = $aes->encrypt($plaintext);

        $ciphertextBuffer = $this->createMock(BufferInterface::class);
        $eofCallCount = 0;
        $ciphertextBuffer->expects($this->atLeast(1))
            ->method('eof')
            ->willReturnCallback(function () use (&$eofCallCount) {
                return $eofCallCount++ > 0;
            });
        $ciphertextBuffer->expects($this->atLeast(1))
            ->method('read')
            ->willReturn($ciphertext);
        $lengthCallCount = 0;
        $ciphertextBuffer->expects($this->atLeast(1))
            ->method('length')
            ->willReturnCallback(function () use ($ciphertext, &$lengthCallCount) {
                return $lengthCallCount++ === 0 ? strlen($ciphertext) : 0;
            });

        $plaintextBuffer = $this->createMock(BufferInterface::class);
        $plaintextBuffer->expects($this->once())
            ->method('write')
            ->with($this->equalTo($plaintext));

        $aes->decryptBuffer($ciphertextBuffer, $plaintextBuffer);
    }
}
