<?php

namespace EbicsApi\Ebics\Tests\Models\Crypt;

use EbicsApi\Ebics\Contracts\BufferInterface;
use EbicsApi\Ebics\Factories\BufferFactory;
use EbicsApi\Ebics\Models\Crypt\AES;
use EbicsApi\Ebics\Tests\AbstractEbicsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class AESTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
class AESTest extends AbstractEbicsTestCase
{
    private string $aesFilename;

    protected function setUp(): void
    {
        $this->aesFilename = tempnam(sys_get_temp_dir(), 'ebics_aes_test');
    }

    protected function tearDown(): void
    {
        foreach ([$this->aesFilename, $this->aesFilename . '_cipher', $this->aesFilename . '_plain'] as $filename) {
            if ($filename !== '' && file_exists($filename)) {
                unlink($filename);
            }
        }
    }

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

    #[DataProvider('decryptBufferProvider')]
    public function testDecryptBufferWithVariousSizes(int $plaintextLength): void
    {
        $key = '1234567890123456';
        $iv = '1234567890123456';
        $plaintext = random_bytes($plaintextLength);

        $encryptAes = new AES();
        $encryptAes->setKey($key);
        $encryptAes->setIV($iv);
        $ciphertext = $encryptAes->encrypt($plaintext);

        $cipherFactory = new BufferFactory($this->aesFilename . '_cipher');
        $ciphertextBuffer = $cipherFactory->createFromContent($ciphertext);

        $decryptAes = new AES();
        $decryptAes->setKey($key);
        $decryptAes->setIV($iv);

        $plainFactory = new BufferFactory($this->aesFilename . '_plain');
        $plaintextBuffer = $plainFactory->create();
        $decryptAes->decryptBuffer($ciphertextBuffer, $plaintextBuffer);

        self::assertEquals($plaintext, $plaintextBuffer->readContent());

        $ciphertextBuffer->close();
        $plaintextBuffer->close();
    }

    public static function decryptBufferProvider(): array
    {
        return [
            '1 byte' => [1],
            '16 bytes' => [16],
            '17 bytes' => [17],
            '31 bytes' => [31],
            '32 bytes' => [32],
            '33 bytes' => [33],
            '100 bytes' => [100],
            '1000 bytes' => [1000],
            '1008 bytes (ciphertext = 1024 = DEFAULT_READ_LENGTH => feof quirk)' => [1008],
            '1009 bytes (ciphertext = 1024 = DEFAULT_READ_LENGTH => feof quirk)' => [1009],
            '1023 bytes (ciphertext = 1024 = DEFAULT_READ_LENGTH => feof quirk)' => [1023],
            '1024 bytes' => [1024],
            '1025 bytes' => [1025],
            '2000 bytes' => [2000],
            '5000 bytes' => [5000],
            '10000 bytes' => [10000],
        ];
    }
}
