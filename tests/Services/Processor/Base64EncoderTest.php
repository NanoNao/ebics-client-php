<?php

namespace EbicsApi\Ebics\Tests\Services\Processor;

use EbicsApi\Ebics\Services\Processor\Base64Encoder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class Base64EncoderTest extends TestCase
{
    private Base64Encoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new Base64Encoder();
    }

    public function testEncodeDecodesRoundtrip(): void
    {
        $data = 'Hello, EBICS world!';

        $encoded = $this->encoder->encode($data);
        $decoded = $this->encoder->decode($encoded);

        self::assertEquals($data, $decoded);
    }

    public function testEncodeEmptyString(): void
    {
        $encoded = $this->encoder->encode('');
        self::assertEquals('', $encoded);
    }

    public function testDecodeEmptyString(): void
    {
        $decoded = $this->encoder->decode('');
        self::assertEquals('', $decoded);
    }

    public function testEncodeProducesValidBase64(): void
    {
        $data = 'binary test data with special chars: !@#$%^&*()';
        $encoded = $this->encoder->encode($data);

        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+=*$/', $encoded);
        self::assertEquals(base64_encode($data), $encoded);
    }

    public function testDecodeProducesCorrectOutput(): void
    {
        $original = 'test data 123';
        $base64 = base64_encode($original);

        self::assertEquals($original, $this->encoder->decode($base64));
    }

    public function testEncodeThrowsOnDataExceedingMaxLength(): void
    {
        $data = str_repeat('A', 7864321);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data exceeds maximum length of');

        $this->encoder->encode($data);
    }

    public function testDecodeThrowsOnDataExceedingMaxLength(): void
    {
        $data = str_repeat('A', 10485761);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Encoded data exceeds maximum length of');

        $this->encoder->decode($data);
    }

    public function testEncodeAcceptsDataAtExactMaxLength(): void
    {
        $data = str_repeat('B', 7864320);

        $encoded = $this->encoder->encode($data);

        self::assertNotEmpty($encoded);
        self::assertEquals($data, $this->encoder->decode($encoded));
    }

    public function testDecodeAcceptsDataAtExactMaxLength(): void
    {
        $data = str_repeat('C', 10485760);

        $decoded = $this->encoder->decode($data);

        self::assertIsString($decoded);
    }

    public function testEncodeBinaryData(): void
    {
        $data = "\x00\x01\x02\x03\xff\xfe\xfd\xfc";
        $encoded = $this->encoder->encode($data);
        $decoded = $this->encoder->decode($encoded);

        self::assertEquals($data, $decoded);
    }
}
