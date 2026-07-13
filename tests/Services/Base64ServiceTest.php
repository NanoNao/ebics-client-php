<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Contracts\BufferInterface;
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
}
<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Models\Buffer;
use EbicsApi\Ebics\Services\Base64Service;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Base64Service::decodeBuffer() chunked decoding.
 *
 * These tests target the bug where decodeBuffer() silently loses the final
 * 1–3 Base64 characters at EOF when the total stream length is not a
 * multiple of 4. Because read() returns up to 1024 bytes, a remainder of
 * 1–3 chars ends up in the final $remainder variable and base64_decode()
 * on an incomplete group returns an empty string (PHP 8+), dropping data.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @group base64-service
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

    /**
     * Well-formed padded base64 whose length is a clean multiple of 1024.
     * This is the "happy path" and should round-trip correctly even with the
     * buggy chunked decoder (because every chunk boundary aligns on a 4-char
     * group).
     */
    public function testDecodeBufferWithMultipleOf1024Length(): void
    {
        // 768 raw bytes → 1024 base64 chars (exactly one chunk)
        $original = str_repeat('A', 768);
        $base64 = base64_encode($original);

        self::assertEquals(1024, strlen($base64));
        self::assertEquals(0, strlen($base64) % 4);

        $input = $this->createBufferFromString($base64);
        $output = $this->createEmptyBuffer();

        $this->base64Service->decodeBuffer($input, $output);

        $output->rewind();
        $decoded = $output->readContent();

        self::assertEquals($original, $decoded);
    }

    /**
     * BUG: total base64 length = 1024 + 1 = 1025 characters.
     *
     * The first chunk decodes perfectly (1024 chars → 768 bytes).
     * The second (last) chunk contains a single leftover character.
     * base64_decode('x') returns '' in PHP 8+, so 6 bits (0.75 byte)
     * are silently discarded.
     */
    public function testDecodeBufferWithOneByteRemainderLosesData(): void
    {
        // 768 raw bytes → 1024 base64 chars. Truncate 3 chars and append 1
        // so the total becomes 1022 chars... wait, we need 1025.
        // Let's just start from raw=1023 → 1364 base64 chars (already >1024).
        // Truncate to 1025 chars: 1364 - 339 = 1025.
        $original = str_repeat('X', 1023);
        $base64 = base64_encode($original);
        $truncatedBase64 = substr($base64, 0, 1025);

        self::assertEquals(1025, strlen($truncatedBase64));
        self::assertEquals(1, strlen($truncatedBase64) % 4);

        $input = $this->createBufferFromString($truncatedBase64);
        $output = $this->createEmptyBuffer();

        $this->base64Service->decodeBuffer($input, $output);

        $output->rewind();
        $decoded = $output->readContent();

        // The buggy implementation loses the last 3 raw bytes because the
        // final 1-char remainder decodes to an empty string.
        self::assertEquals($original, $decoded);
    }

    /**
     * BUG: total base64 length = 1024 + 2 = 1026 characters.
     *
     * The final 2-char remainder is passed to base64_decode(), which
     * returns 1 incorrect byte in PHP 8+. Two raw bytes are lost.
     */
    public function testDecodeBufferWithTwoByteRemainderLosesData(): void
    {
        $original = str_repeat('Y', 1023);
        $base64 = base64_encode($original);
        $truncatedBase64 = substr($base64, 0, 1026);

        self::assertEquals(1026, strlen($truncatedBase64));
        self::assertEquals(2, strlen($truncatedBase64) % 4);

        $input = $this->createBufferFromString($truncatedBase64);
        $output = $this->createEmptyBuffer();

        $this->base64Service->decodeBuffer($input, $output);

        $output->rewind();
        $decoded = $output->readContent();

        self::assertEquals($original, $decoded);
    }

    /**
     * BUG: total base64 length = 1024 + 3 = 1027 characters.
     *
     * The final 3-char remainder decodes to 2 bytes (often garbage) in
     * PHP 8+, so 1 raw byte is lost.
     */
    public function testDecodeBufferWithThreeByteRemainderLosesData(): void
    {
        $original = str_repeat('Z', 1023);
        $base64 = base64_encode($original);
        $truncatedBase64 = substr($base64, 0, 1027);

        self::assertEquals(1027, strlen($truncatedBase64));
        self::assertEquals(3, strlen($truncatedBase64) % 4);

        $input = $this->createBufferFromString($truncatedBase64);
        $output = $this->createEmptyBuffer();

        $this->base64Service->decodeBuffer($input, $output);

        $output->rewind();
        $decoded = $output->readContent();

        self::assertEquals($original, $decoded);
    }
}
