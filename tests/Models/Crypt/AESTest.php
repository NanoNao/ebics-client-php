<?php

namespace EbicsApi\Ebics\Tests\Models\Crypt;

use EbicsApi\Ebics\Models\Crypt\AES;
use PHPUnit\Framework\TestCase;

class AESTest extends TestCase
{
    private AES $aes;

    protected function setUp(): void
    {
        $this->aes = new AES();
    }

    public function testEncryptBlockDecryptBlockRoundtrip(): void
    {
        $key = '1234567890123456';
        $iv = str_repeat("\0", 16);
        $block = str_repeat('A', 16);

        $encrypted = $this->aes->encryptBlock($block, $key, 'aes-128-cbc', $iv);
        $decrypted = $this->aes->decryptBlock($encrypted, $key, 'aes-128-cbc', $iv);

        self::assertEquals($block, $decrypted);
    }

    public function testPadEncryptBlockDecryptBlockUnpadRoundtrip(): void
    {
        $key = '1234567890123456';
        $iv = str_repeat("\0", 16);
        $plaintext = 'Hello World!';

        $padded = $this->aes->pad($plaintext);
        $encrypted = $this->aes->encryptBlock($padded, $key, 'aes-128-cbc', $iv);
        $decrypted = $this->aes->decryptBlock($encrypted, $key, 'aes-128-cbc', $iv);
        $result = $this->aes->unpad($decrypted);

        self::assertEquals($plaintext, $result);
    }

    public function testPadEncryptBlockDecryptBlockUnpadBlockSizeData(): void
    {
        $key = '1234567890123456';
        $iv = str_repeat("\0", 16);
        $plaintext = 'Hello World!!';

        $padded = $this->aes->pad($plaintext);
        $encrypted = $this->aes->encryptBlock($padded, $key, 'aes-128-cbc', $iv);
        $decrypted = $this->aes->decryptBlock($encrypted, $key, 'aes-128-cbc', $iv);
        $result = $this->aes->unpad($decrypted);

        self::assertEquals($plaintext, $result);
        self::assertEquals(16, strlen($encrypted));
    }

    public function testPadEncryptBlockDecryptBlockUnpadBinaryData(): void
    {
        $key = '1234567890123456';
        $iv = str_repeat("\0", 16);
        $plaintext = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\xff\xfe\xfd";

        $padded = $this->aes->pad($plaintext);
        $encrypted = $this->aes->encryptBlock($padded, $key, 'aes-128-cbc', $iv);
        $decrypted = $this->aes->decryptBlock($encrypted, $key, 'aes-128-cbc', $iv);
        $result = $this->aes->unpad($decrypted);

        self::assertEquals($plaintext, $result);
    }

    public function testEncryptBlockDifferentIvsProduceDifferentCiphertext(): void
    {
        $key = '1234567890123456';
        $block = str_repeat('A', 16);

        $encrypted1 = $this->aes->encryptBlock($block, $key, 'aes-128-cbc', '1234567890123456');
        $encrypted2 = $this->aes->encryptBlock($block, $key, 'aes-128-cbc', '6543210987654321');

        self::assertNotEquals($encrypted1, $encrypted2);
    }

    public function testPadUnpadRoundtrip(): void
    {
        $data = 'Hello';
        $padded = $this->aes->pad($data);
        $unpadded = $this->aes->unpad($padded);

        self::assertEquals($data, $unpadded);
        self::assertEquals(16, strlen($padded));
    }

    public function testPadProducesBlockAlignedOutput(): void
    {
        $data = 'Short';

        $padded = $this->aes->pad($data);

        self::assertEquals(0, strlen($padded) % 16);
    }

    public function testGetBlockSize(): void
    {
        self::assertEquals(16, $this->aes->getBlockSize());
    }

    public function testEncryptBlockThrowsOnUnknownCipher(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown cipher: invalid-cipher');

        $this->aes->encryptBlock('test', '1234567890123456', 'invalid-cipher', str_repeat("\0", 16));
    }

    public function testDecryptBlockThrowsOnUnknownCipher(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown cipher: invalid-cipher');

        $this->aes->decryptBlock('test', '1234567890123456', 'invalid-cipher', str_repeat("\0", 16));
    }

    public function testEncryptBlockThrowsOnShortIv(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('IV length must be 16 bytes, got 12.');

        $this->aes->encryptBlock('test', '1234567890123456', 'aes-128-cbc', 'too-short-iv');
    }

    public function testDecryptBlockThrowsOnShortIv(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('IV length must be 16 bytes, got 12.');

        $this->aes->decryptBlock('test', '1234567890123456', 'aes-128-cbc', 'too-short-iv');
    }

    public function testEncryptBlockThrowsOnLongIv(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('IV length must be 16 bytes, got 20.');

        $this->aes->encryptBlock('test', '1234567890123456', 'aes-128-cbc', str_repeat('x', 20));
    }

    public function testDecryptBlockThrowsOnLongIv(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('IV length must be 16 bytes, got 20.');

        $this->aes->decryptBlock('test', '1234567890123456', 'aes-128-cbc', str_repeat('x', 20));
    }

    public function testEncryptBlockAcceptsValidIv(): void
    {
        $key = '1234567890123456';
        $iv = str_repeat("\1", 16);
        $block = str_repeat('A', 16);

        $encrypted = $this->aes->encryptBlock($block, $key, 'aes-128-cbc', $iv);
        $decrypted = $this->aes->decryptBlock($encrypted, $key, 'aes-128-cbc', $iv);

        self::assertSame($block, $decrypted);
    }

    public function testEncryptBlockAcceptsValidCiphers(): void
    {
        $key16 = '1234567890123456';
        $key24 = '123456789012345678901234';
        $key32 = '12345678901234561234567890123456';
        $iv = str_repeat("\0", 16);
        $block = str_repeat('A', 16);

        $encrypted128 = $this->aes->encryptBlock($block, $key16, 'aes-128-cbc', $iv);
        self::assertNotEmpty($encrypted128);

        $encrypted192 = $this->aes->encryptBlock($block, $key24, 'aes-192-cbc', $iv);
        self::assertNotEmpty($encrypted192);

        $encrypted256 = $this->aes->encryptBlock($block, $key32, 'aes-256-cbc', $iv);
        self::assertNotEmpty($encrypted256);
    }
}
