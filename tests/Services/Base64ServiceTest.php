<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Contracts\BufferInterface;
use EbicsApi\Ebics\Factories\Crypt\AESFactory;
use EbicsApi\Ebics\Models\Buffer;
use EbicsApi\Ebics\Services\Base64Service;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Base64Service.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class Base64ServiceTest extends TestCase
{
    private Base64Service $base64Service;

    protected function setUp(): void
    {
        $this->base64Service = new Base64Service();
    }

    private function createBufferFromString(string $content): Buffer
    {
        $filename = tempnam(sys_get_temp_dir(), 'ebics_base64_test_');
        file_put_contents($filename, $content);
        $buffer = new Buffer($filename);
        $buffer->open('r');
        $buffer->rewind();

        return $buffer;
    }

    private function createEmptyBuffer(): Buffer
    {
        $filename = tempnam(sys_get_temp_dir(), 'ebics_base64_test_');
        $buffer = new Buffer($filename);
        $buffer->open('w+');

        return $buffer;
    }

    public function testEncodeReturnsBase64String(): void
    {
        $data = 'Hello World';

        $encoded = $this->base64Service->encode($data);

        self::assertEquals(base64_encode($data), $encoded);
    }

    public function testEncodeEmptyString(): void
    {
        $encoded = $this->base64Service->encode('');

        self::assertEquals('', $encoded);
    }

    public function testEncodeBinaryData(): void
    {
        $data = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09";

        $encoded = $this->base64Service->encode($data);

        self::assertEquals(base64_encode($data), $encoded);
    }

    public function testDecodeReturnsRawString(): void
    {
        $data = 'Hello World';
        $encoded = base64_encode($data);

        $decoded = $this->base64Service->decode($encoded);

        self::assertEquals($data, $decoded);
    }

    public function testDecodeEmptyString(): void
    {
        $decoded = $this->base64Service->decode('');

        self::assertEquals('', $decoded);
    }

    public function testEncodeDecodeRoundtrip(): void
    {
        $data = 'Roundtrip test data that should survive encoding and decoding';

        $encoded = $this->base64Service->encode($data);
        $decoded = $this->base64Service->decode($encoded);

        self::assertEquals($data, $decoded);
    }

    public function testEncodeDecodeBinaryRoundtrip(): void
    {
        $data = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\xff\xfe\xfd";

        $encoded = $this->base64Service->encode($data);
        $decoded = $this->base64Service->decode($encoded);

        self::assertEquals($data, $decoded);
    }

    public function testEncodeBufferEmptyInput(): void
    {
        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        $inputBuffer = new Buffer($inputFile);
        $inputBuffer->open('w+');
        $inputBuffer->rewind();

        $outputBuffer = new Buffer($outputFile);
        $outputBuffer->open('w+');

        $this->base64Service->encodeBuffer($inputBuffer, $outputBuffer);

        $outputBuffer->rewind();
        $result = $outputBuffer->readContent();

        self::assertEquals('', $result);

        $inputBuffer->close();
        $outputBuffer->close();
        @unlink($inputFile);
        @unlink($outputFile);
    }

    public function testDecodeBufferEmptyInput(): void
    {
        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        $inputBuffer = new Buffer($inputFile);
        $inputBuffer->open('w+');
        $inputBuffer->rewind();

        $outputBuffer = new Buffer($outputFile);
        $outputBuffer->open('w+');

        $this->base64Service->decodeBuffer($inputBuffer, $outputBuffer);

        $outputBuffer->rewind();
        $result = $outputBuffer->readContent();

        self::assertEquals('', $result);

        $inputBuffer->close();
        $outputBuffer->close();
        @unlink($inputFile);
        @unlink($outputFile);
    }

    public function testEncodeBufferWithBinaryData(): void
    {
        $originalData = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\xff\xfe\xfd";

        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        $inputBuffer = new Buffer($inputFile);
        $inputBuffer->open('w+');
        $inputBuffer->write($originalData);
        $inputBuffer->rewind();

        $outputBuffer = new Buffer($outputFile);
        $outputBuffer->open('w+');

        $this->base64Service->encodeBuffer($inputBuffer, $outputBuffer);

        $outputBuffer->rewind();
        $bufferedResult = $outputBuffer->readContent();

        $nonBufferedResult = $this->base64Service->encode($originalData);

        self::assertEquals($nonBufferedResult, $bufferedResult);

        $inputBuffer->close();
        $outputBuffer->close();
        @unlink($inputFile);
        @unlink($outputFile);
    }

    public function testDecodeBufferWithBinaryData(): void
    {
        $originalData = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\xff\xfe\xfd";
        $encodedData = $this->base64Service->encode($originalData);

        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        $inputBuffer = new Buffer($inputFile);
        $inputBuffer->open('w+');
        $inputBuffer->write($encodedData);
        $inputBuffer->rewind();

        $outputBuffer = new Buffer($outputFile);
        $outputBuffer->open('w+');

        $this->base64Service->decodeBuffer($inputBuffer, $outputBuffer);

        $outputBuffer->rewind();
        $bufferedResult = $outputBuffer->readContent();

        $nonBufferedResult = $this->base64Service->decode($encodedData);

        self::assertEquals($nonBufferedResult, $bufferedResult);

        $inputBuffer->close();
        $outputBuffer->close();
        @unlink($inputFile);
        @unlink($outputFile);
    }

    public function testEncodeStringDecodeBufferCrossCompatibility(): void
    {
        $data = 'Cross-compatibility test: encode string, decode buffer';

        $encoded = $this->base64Service->encode($data);

        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        $inputBuffer = new Buffer($inputFile);
        $inputBuffer->open('w+');
        $inputBuffer->write($encoded);
        $inputBuffer->rewind();

        $outputBuffer = new Buffer($outputFile);
        $outputBuffer->open('w+');

        $this->base64Service->decodeBuffer($inputBuffer, $outputBuffer);

        $outputBuffer->rewind();
        $result = $outputBuffer->readContent();

        self::assertEquals($data, $result);

        $inputBuffer->close();
        $outputBuffer->close();
        @unlink($inputFile);
        @unlink($outputFile);
    }

    public function testEncodeBufferDecodeStringCrossCompatibility(): void
    {
        $data = 'Cross-compatibility test: encode buffer, decode string';

        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        $inputBuffer = new Buffer($inputFile);
        $inputBuffer->open('w+');
        $inputBuffer->write($data);
        $inputBuffer->rewind();

        $outputBuffer = new Buffer($outputFile);
        $outputBuffer->open('w+');

        $this->base64Service->encodeBuffer($inputBuffer, $outputBuffer);

        $outputBuffer->rewind();
        $encoded = $outputBuffer->readContent();

        $decoded = $this->base64Service->decode($encoded);

        self::assertEquals($data, $decoded);

        $inputBuffer->close();
        $outputBuffer->close();
        @unlink($inputFile);
        @unlink($outputFile);
    }

    #[DataProvider('encodeBufferWithVariousSizesProvider')]
    public function testEncodeBufferWithVariousSizes(int $dataLength): void
    {
        $originalData = str_repeat('A', $dataLength);

        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        $inputBuffer = new Buffer($inputFile);
        $inputBuffer->open('w+');
        $inputBuffer->write($originalData);
        $inputBuffer->rewind();

        $outputBuffer = new Buffer($outputFile);
        $outputBuffer->open('w+');

        $this->base64Service->encodeBuffer($inputBuffer, $outputBuffer);

        $outputBuffer->rewind();
        $bufferedResult = $outputBuffer->readContent();

        $nonBufferedResult = $this->base64Service->encode($originalData);

        self::assertEquals($nonBufferedResult, $bufferedResult);

        $inputBuffer->close();
        $outputBuffer->close();
        @unlink($inputFile);
        @unlink($outputFile);
    }

    #[DataProvider('encodeBufferWithVariousSizesProvider')]
    public function testDecodeBufferWithVariousSizes(int $dataLength): void
    {
        $originalData = str_repeat('B', $dataLength);
        $encodedData = $this->base64Service->encode($originalData);

        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        $inputBuffer = new Buffer($inputFile);
        $inputBuffer->open('w+');
        $inputBuffer->write($encodedData);
        $inputBuffer->rewind();

        $outputBuffer = new Buffer($outputFile);
        $outputBuffer->open('w+');

        $this->base64Service->decodeBuffer($inputBuffer, $outputBuffer);

        $outputBuffer->rewind();
        $bufferedResult = $outputBuffer->readContent();

        $nonBufferedResult = $this->base64Service->decode($encodedData);

        self::assertEquals($nonBufferedResult, $bufferedResult);

        $inputBuffer->close();
        $outputBuffer->close();
        @unlink($inputFile);
        @unlink($outputFile);
    }

    #[DataProvider('encodeBufferWithVariousSizesProvider')]
    public function testEncodeBufferDecodeBufferRoundtrip(int $dataLength): void
    {
        $originalData = str_repeat('C', $dataLength);

        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $encodedFile = tempnam(sys_get_temp_dir(), 'b64_enc_');
        $decodedFile = tempnam(sys_get_temp_dir(), 'b64_dec_');

        $inputBuffer = new Buffer($inputFile);
        $inputBuffer->open('w+');
        $inputBuffer->write($originalData);
        $inputBuffer->rewind();

        $encodedBuffer = new Buffer($encodedFile);
        $encodedBuffer->open('w+');

        $this->base64Service->encodeBuffer($inputBuffer, $encodedBuffer);

        $encodedBuffer->rewind();

        $decodedBuffer = new Buffer($decodedFile);
        $decodedBuffer->open('w+');

        $this->base64Service->decodeBuffer($encodedBuffer, $decodedBuffer);

        $decodedBuffer->rewind();
        $result = $decodedBuffer->readContent();

        self::assertEquals($originalData, $result);

        $inputBuffer->close();
        $encodedBuffer->close();
        $decodedBuffer->close();
        @unlink($inputFile);
        @unlink($encodedFile);
        @unlink($decodedFile);
    }

    #[DataProvider('decodeBufferChunkBoundaryProvider')]
    public function testDecodeBufferLosesDataAtChunkBoundary(int $binaryLength): void
    {
        $originalData = random_bytes($binaryLength);
        $encodedPadded = base64_encode($originalData);
        $encodedUnpadded = rtrim($encodedPadded, '=');

        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        try {
            $inputBuffer = new Buffer($inputFile);
            $inputBuffer->open('w+');
            $inputBuffer->write($encodedUnpadded);
            $inputBuffer->rewind();

            $outputBuffer = new Buffer($outputFile);
            $outputBuffer->open('w+');

            $this->base64Service->decodeBuffer($inputBuffer, $outputBuffer);

            $outputBuffer->rewind();
            $result = $outputBuffer->readContent();

            $expected = base64_decode($encodedUnpadded);

            self::assertSame(
                $expected,
                $result,
                sprintf(
                    'decodeBuffer lost %d bytes: unpadded base64 length %d, remainder %d',
                    strlen($expected) - strlen($result),
                    strlen($encodedUnpadded),
                    strlen($encodedUnpadded) % BufferInterface::DEFAULT_READ_LENGTH
                )
            );
        } finally {
            @unlink($inputFile);
            @unlink($outputFile);
        }
    }

    public static function decodeBufferChunkBoundaryProvider(): array
    {
        return [
            '768 bytes binary → 1024 base64 chars (no remainder)' => [768],
            '769 bytes binary → 1026 unpadded chars (2-byte remainder)' => [769],
            '770 bytes binary → 1027 unpadded chars (3-byte remainder)' => [770],
            '771 bytes binary → 1028 base64 chars (4-byte remainder, no bug)' => [771],
            '772 bytes binary → 1030 unpadded chars (6-byte remainder)' => [772],
            '1025 bytes binary → 1367 unpadded chars (343-byte remainder)' => [1025],
            '2048 bytes binary → 2731 unpadded chars (683-byte remainder)' => [2048],
            '3072 bytes binary → 4096 base64 chars (no remainder)' => [3072],
            '3073 bytes binary → 4098 unpadded chars (2-byte remainder)' => [3073],
            '5000 bytes binary → 6667 unpadded chars (523-byte remainder)' => [5000],
        ];
    }

    public function testDecodeBufferSmallUnpaddedRemainder1Byte(): void
    {
        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        try {
            $inputBuffer = new Buffer($inputFile);
            $inputBuffer->open('w+');
            $inputBuffer->write(str_repeat('A', 1024) . 'Q');
            $inputBuffer->rewind();

            $outputBuffer = new Buffer($outputFile);
            $outputBuffer->open('w+');

            $this->base64Service->decodeBuffer($inputBuffer, $outputBuffer);

            $outputBuffer->rewind();
            $result = $outputBuffer->readContent();

            $expected = base64_decode(str_repeat('A', 1024) . 'Q');

            self::assertSame($expected, $result);
        } finally {
            @unlink($inputFile);
            @unlink($outputFile);
        }
    }

    public function testDecodeBufferSmallUnpaddedRemainder2Bytes(): void
    {
        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        try {
            $inputBuffer = new Buffer($inputFile);
            $inputBuffer->open('w+');
            $inputBuffer->write(str_repeat('A', 1024) . 'QQ');
            $inputBuffer->rewind();

            $outputBuffer = new Buffer($outputFile);
            $outputBuffer->open('w+');

            $this->base64Service->decodeBuffer($inputBuffer, $outputBuffer);

            $outputBuffer->rewind();
            $result = $outputBuffer->readContent();

            $expected = base64_decode(str_repeat('A', 1024) . 'QQ');

            self::assertSame($expected, $result);
        } finally {
            @unlink($inputFile);
            @unlink($outputFile);
        }
    }

    public function testDecodeBufferSmallUnpaddedRemainder3Bytes(): void
    {
        $inputFile = tempnam(sys_get_temp_dir(), 'b64_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'b64_out_');

        try {
            $inputBuffer = new Buffer($inputFile);
            $inputBuffer->open('w+');
            $inputBuffer->write(str_repeat('A', 1024) . 'QQQ');
            $inputBuffer->rewind();

            $outputBuffer = new Buffer($outputFile);
            $outputBuffer->open('w+');

            $this->base64Service->decodeBuffer($inputBuffer, $outputBuffer);

            $outputBuffer->rewind();
            $result = $outputBuffer->readContent();

            $expected = base64_decode(str_repeat('A', 1024) . 'QQQ');

            self::assertSame($expected, $result);
        } finally {
            @unlink($inputFile);
            @unlink($outputFile);
        }
    }

    public static function encodeBufferWithVariousSizesProvider(): array
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
            '1008 bytes (input = 1024 = DEFAULT_READ_LENGTH => feof quirk)' => [1008],
            '1009 bytes (input = 1024 = DEFAULT_READ_LENGTH => feof quirk)' => [1009],
            '1023 bytes (input = 1024 = DEFAULT_READ_LENGTH => feof quirk)' => [1023],
            '1024 bytes' => [1024],
            '1025 bytes' => [1025],
            '2000 bytes' => [2000],
            '5000 bytes' => [5000],
            '10000 bytes' => [10000],
        ];
    }

    /**
     * Core correctness check: decodeBuffer() must match base64_decode() on the
     * exact same input for every size listed.
     *
     * We use unpadded base64 (rtrim(..., '=')) because that is the realistic
     * EBICS scenario where a trailing group of 1–3 characters can appear.
     */
    #[DataProvider('variousSizesProvider')]
    public function testDecodeBufferMatchesOneshotBase64Decode(int $rawSize): void
    {
        $original = random_bytes($rawSize);
        $unpaddedBase64 = rtrim(base64_encode($original), '=');

        $input = $this->createBufferFromString($unpaddedBase64);
        $output = $this->createEmptyBuffer();

        $this->base64Service->decodeBuffer($input, $output);

        $output->rewind();
        $decoded = $output->readContent();

        // Reference: native base64_decode on the exact same string.
        $expected = base64_decode($unpaddedBase64);

        self::assertEquals(
            $expected,
            $decoded,
            sprintf(
                "decodeBuffer() output (%d bytes) differs from base64_decode() (%d bytes) for rawSize=%d, b64Len=%d, b64Mod4=%d",
                strlen($decoded),
                strlen($expected),
                $rawSize,
                strlen($unpaddedBase64),
                strlen($unpaddedBase64) % 4
            )
        );
    }

    public static function variousSizesProvider(): array
    {
        return [
            // Small remainders that never cross a chunk boundary
            '1 byte raw' => [1],
            '2 bytes raw' => [2],
            '3 bytes raw' => [3],

            // Sizes where base64 length mod 4 == 0 (clean groups)
            '6 bytes raw (b64 mod4=0)' => [6],
            '1023 bytes raw (b64 mod4=0)' => [1023],

            // Sizes that produce a base64 remainder of 1–3 at EOF
            '7 bytes raw (b64 mod4=3)' => [7],
            '8 bytes raw (b64 mod4=2)' => [8],
            '9 bytes raw (b64 mod4=1)' => [9],

            // Sizes around the internal 1024-byte read chunk
            '768 bytes raw (b64 = 1024 chars)' => [768],
            '1024 bytes raw' => [1024],
            '1025 bytes raw' => [1025],

            // Large sizes that cross multiple chunk boundaries
            '2000 bytes raw' => [2000],
            '5000 bytes raw' => [5000],
        ];
    }

    /**
     * Demonstrate the bug that occurs when base64_decode() is applied to
     * fixed-size chunks without carrying a remainder across boundaries.
     *
     * Because base64 groups are 4 characters, a chunk size that is NOT a
     * multiple of 4 (e.g. 1023) cuts through a group at every boundary.
     * The leftover 1–3 characters at the end of each chunk are decoded
     * independently and therefore produce incorrect / truncated bytes.
     *
     * The current Base64Service avoids this by accumulating $remainder and
     * prepending it to the next chunk, so group boundaries are never split.
     */
    public function testChunkedDecodeWithoutRemainderLosesBytesWhenChunkSizeNotAligned(): void
    {
        $original = random_bytes(1023); // unpadded b64 length = 1364
        $unpaddedBase64 = rtrim(base64_encode($original), '=');

        $chunkSize = 1023; // NOT a multiple of 4 → group boundaries are split

        $buggyDecoded = '';
        $pos = 0;
        $len = strlen($unpaddedBase64);
        while ($pos < $len) {
            $chunk = substr($unpaddedBase64, $pos, $chunkSize);
            $buggyDecoded .= base64_decode($chunk);
            $pos += $chunkSize;
        }

        $correctDecoded = base64_decode($unpaddedBase64);

        self::assertNotEquals(
            $correctDecoded,
            $buggyDecoded,
            "Chunked decode without remainder must lose bytes when chunk size ($chunkSize) is not a multiple of 4"
        );
    }

    /**
     * BUG: truncated ciphertext after buggy chunked base64 decode causes
     * AES::unpad() to throw LogicException: "Length incorrect."
     *
     * Flow:
     *   1. Encrypt a plaintext with AES-128-CBC.
     *   2. Encode the ciphertext to unpadded base64.
     *   3. Decode with the buggy no-remainder approach (chunk size 1023).
     *      This drops 1-3 bytes from the ciphertext.
     *   4. Feed the truncated ciphertext to AES::decryptBuffer().
     *   5. The ciphertext is no longer a multiple of 16, so unpad() fails.
     */
    public function testBuggyChunkedBase64DecodeCorruptsAesCiphertext(): void
    {
        $aesFactory = new AESFactory();
        $aes = $aesFactory->create();
        $aes->setKeyLength(128);
        $key = str_repeat('K', 16);
        $aes->setKey($key);
        $aes->setOpenSSLOptions(OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        $plaintext = str_repeat('A', 2000); // 2000 bytes → padded to 2000+16 = 2016-byte ciphertext
        $ciphertext = $aes->encrypt($plaintext);
        self::assertGreaterThanOrEqual(1025, strlen($ciphertext));

        $unpaddedBase64 = rtrim(base64_encode($ciphertext), '=');
        self::assertGreaterThan(1024, strlen($unpaddedBase64), 'Need b64 > 1024 to span multiple chunks');

        // Buggy decode: chunk size NOT a multiple of 4 → bytes lost at every boundary
        $buggyCiphertext = '';
        $pos = 0;
        $len = strlen($unpaddedBase64);
        $chunkSize = 1023;
        while ($pos < $len) {
            $chunk = substr($unpaddedBase64, $pos, $chunkSize);
            $buggyCiphertext .= base64_decode($chunk);
            $pos += $chunkSize;
        }

        // The buggy ciphertext is shorter → no longer a block-aligned size
        self::assertLessThan(strlen($ciphertext), strlen($buggyCiphertext));

        $cipherBuffer = $this->createBufferFromString($buggyCiphertext);
        $plainBuffer = $this->createEmptyBuffer();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Length incorrect.');

        $aes->decryptBuffer($cipherBuffer, $plainBuffer);
    }
}
