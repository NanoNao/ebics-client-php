<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Models\Buffer;
use EbicsApi\Ebics\Services\ZipService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for ZipService.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class ZipServiceTest extends TestCase
{
    private ZipService $zipService;

    protected function setUp(): void
    {
        $this->zipService = new ZipService();
    }

    /**
     * Test extractFilesFromString with valid ZIP archive.
     */
    public function testExtractFilesFromStringWithValidZip(): void
    {
        // Create a simple ZIP archive in memory
        $tempFile = tempnam(sys_get_temp_dir(), 'zip_test_');
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('test.txt', 'Hello World');
        $zip->addFromString('test2.txt', 'Second file');
        $zip->close();

        $zipContent = file_get_contents($tempFile);
        unlink($tempFile);

        if ($zipContent === false) {
            self::fail('Could not read ZIP content');
        }

        $files = $this->zipService->extractFilesFromString($zipContent);

        self::assertIsArray($files);
        self::assertArrayHasKey('test.txt', $files);
        self::assertArrayHasKey('test2.txt', $files);
        self::assertEquals('Hello World', $files['test.txt']);
        self::assertEquals('Second file', $files['test2.txt']);
    }

    /**
     * Test extractFilesFromString throws exception for invalid ZIP.
     */
    public function testExtractFilesFromStringThrowsExceptionForInvalidZip(): void
    {
        $invalidContent = 'this is not a zip file';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Zip archive was not opened.');

        $this->zipService->extractFilesFromString($invalidContent);
    }

    /**
     * Test extractFilesFromString with subdirectory in ZIP.
     */
    public function testExtractFilesFromStringWithSubdirectory(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'zip_test_');
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('subdir/file.txt', 'Nested content');
        $zip->close();

        $zipContent = file_get_contents($tempFile);
        unlink($tempFile);

        if ($zipContent === false) {
            self::fail('Could not read ZIP content');
        }

        $files = $this->zipService->extractFilesFromString($zipContent);

        self::assertArrayHasKey('subdir/file.txt', $files);
        self::assertEquals('Nested content', $files['subdir/file.txt']);
    }

    /**
     * Test compress and verify output is not empty.
     */
    public function testCompressProducesNonEmptyOutput(): void
    {
        $data = 'Test data to compress';

        $compressed = $this->zipService->compress($data);

        self::assertIsString($compressed);
        self::assertNotEmpty($compressed);
        // Compressed data should be different from original
        self::assertNotEquals($data, $compressed);
    }

    /**
     * Test compress with empty string.
     */
    public function testCompressEmptyString(): void
    {
        $compressed = $this->zipService->compress('');

        self::assertIsString($compressed);
        self::assertNotEmpty($compressed);
    }

    /**
     * Test compress with binary data.
     */
    public function testCompressBinaryData(): void
    {
        $binaryData = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09";

        $compressed = $this->zipService->compress($binaryData);

        self::assertIsString($compressed);
        self::assertNotEmpty($compressed);
    }

    /**
     * Test uncompress gzip-compressed data.
     */
    public function testUncompressGzipData(): void
    {
        $originalData = 'This is test data for gzip compression';

        // Create gzip compressed data using gzcompress (zlib format)
        $compressedData = gzcompress($originalData);

        if ($compressedData === false) {
            self::fail('Could not compress data');
        }

        // Create buffers
        $compressedFile = tempnam(sys_get_temp_dir(), 'compressed_');
        $uncompressedFile = tempnam(sys_get_temp_dir(), 'uncompressed_');

        $compressedBuffer = new Buffer($compressedFile);
        $compressedBuffer->open('w+');
        $compressedBuffer->write($compressedData);
        $compressedBuffer->rewind();

        $uncompressedBuffer = new Buffer($uncompressedFile);
        $uncompressedBuffer->open('w+');

        $this->zipService->uncompress($compressedBuffer, $uncompressedBuffer);

        $uncompressedBuffer->rewind();
        $result = $uncompressedBuffer->readContent();

        self::assertEquals($originalData, $result);

        // Cleanup
        $compressedBuffer->close();
        $uncompressedBuffer->close();
        @unlink($compressedFile);
        @unlink($uncompressedFile);
    }

    /**
     * Test compress and uncompress roundtrip.
     */
    public function testCompressUncompressRoundtrip(): void
    {
        $originalData = 'Roundtrip test data that should survive compression and decompression';

        // Compress
        $compressedData = $this->zipService->compress($originalData);

        // Create buffers for uncompress
        $compressedFile = tempnam(sys_get_temp_dir(), 'compressed_');
        $uncompressedFile = tempnam(sys_get_temp_dir(), 'uncompressed_');

        $compressedBuffer = new Buffer($compressedFile);
        $compressedBuffer->open('w+');
        $compressedBuffer->write($compressedData);
        $compressedBuffer->rewind();

        $uncompressedBuffer = new Buffer($uncompressedFile);
        $uncompressedBuffer->open('w+');

        $this->zipService->uncompress($compressedBuffer, $uncompressedBuffer);

        $uncompressedBuffer->rewind();
        $result = $uncompressedBuffer->readContent();

        self::assertEquals($originalData, $result);

        // Cleanup
        $compressedBuffer->close();
        $uncompressedBuffer->close();
        @unlink($compressedFile);
        @unlink($uncompressedFile);
    }
}
