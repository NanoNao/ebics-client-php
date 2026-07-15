<?php

namespace EbicsApi\Ebics\Tests\Services\Processor;

use EbicsApi\Ebics\Services\Processor\ZipCompressor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ZipCompressorTest extends TestCase
{
    private ZipCompressor $zipCompressor;

    protected function setUp(): void
    {
        $this->zipCompressor = new ZipCompressor();
    }

    public function testCompressUncompressRoundtrip(): void
    {
        $data = 'Hello World! This is test data for compression roundtrip.';

        $compressed = $this->zipCompressor->compress($data);
        $result = $this->zipCompressor->uncompress($compressed);

        self::assertEquals($data, $result);
    }

    public function testCompressUncompressEmptyString(): void
    {
        $compressed = $this->zipCompressor->compress('');
        $result = $this->zipCompressor->uncompress($compressed);

        self::assertEquals('', $result);
    }

    public function testCompressUncompressBinaryData(): void
    {
        $data = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\xff\xfe\xfd";

        $compressed = $this->zipCompressor->compress($data);
        $result = $this->zipCompressor->uncompress($compressed);

        self::assertEquals($data, $result);
    }

    public function testCompressUncompressLargeData(): void
    {
        $data = str_repeat('ABCDEFGHIJ', 10000);

        $compressed = $this->zipCompressor->compress($data);
        $result = $this->zipCompressor->uncompress($compressed);

        self::assertEquals($data, $result);
    }

    public function testUncompressThrowsOnTooShortData(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid zlib data: need at least 2 bytes for header');

        $this->zipCompressor->uncompress('x');
    }

    public function testUncompressThrowsOnInvalidHeader(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid zlib header');

        $this->zipCompressor->uncompress('not compressed data');
    }

    public function testCompressProducesDifferentOutput(): void
    {
        $data = 'Test data';

        $compressed = $this->zipCompressor->compress($data);

        self::assertNotEquals($data, $compressed);
    }

    public function testCompressExceedsMaxDataLength(): void
    {
        $data = str_repeat('A', 10485761);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data exceeds maximum length of');

        $this->zipCompressor->compress($data);
    }

    public function testUncompressExceedsMaxDataLength(): void
    {
        $data = str_repeat('A', 10485761);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data exceeds maximum length of');

        $this->zipCompressor->uncompress($data);
    }
}
