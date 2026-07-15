<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Services\ZipArchiveExtractor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for ZipArchiveExtractor.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class ZipArchiveExtractorTest extends TestCase
{
    private ZipArchiveExtractor $zipArchiveExtractor;

    protected function setUp(): void
    {
        $this->zipArchiveExtractor = new ZipArchiveExtractor();
    }

    public function testExtractFilesFromStringWithValidZip(): void
    {
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

        $files = $this->zipArchiveExtractor->extractFilesFromString($zipContent);

        self::assertIsArray($files);
        self::assertArrayHasKey('test.txt', $files);
        self::assertArrayHasKey('test2.txt', $files);
        self::assertEquals('Hello World', $files['test.txt']);
        self::assertEquals('Second file', $files['test2.txt']);
    }

    public function testExtractFilesFromStringThrowsExceptionForInvalidZip(): void
    {
        $invalidContent = 'this is not a zip file';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Zip archive was not opened.');

        $this->zipArchiveExtractor->extractFilesFromString($invalidContent);
    }

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

        $files = $this->zipArchiveExtractor->extractFilesFromString($zipContent);

        self::assertArrayHasKey('subdir/file.txt', $files);
        self::assertEquals('Nested content', $files['subdir/file.txt']);
    }
}
