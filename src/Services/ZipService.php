<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Models\Buffer;
use RuntimeException;
use ZipArchive;

/**
 * ZIP and gzip compression/decompression service for EBICS order data.
 *
 * Handles compression operations for EBICS file transfer orders:
 * - Extracting files from ZIP archives (e.g., when banks send multiple files)
 * - Compressing order data before sending to the bank (gzip)
 * - Decompressing bank responses (gzip inflate)
 *
 * EBICS banks often compress order data (like account statements or payment
 * files) using ZIP or gzip formats. This service provides methods to handle
 * both formats transparently.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal This class is for internal use and may change without notice.
 */
final class ZipService
{
    /**
     * Create a temporary file in the system temp directory.
     *
     * @return string Path to the created temporary file
     * @throws RuntimeException If the temporary file cannot be created
     */
    private function createTmpFile(): string
    {
        if (!($path = tempnam(sys_get_temp_dir(), 'ebics-file'))) {
            throw new RuntimeException('Can not create temporary dir.');
        }

        return $path;
    }

    /**
     * Extract all files from a ZIP-compressed string.
     *
     * Creates a temporary file, writes the ZIP content to it, extracts all files
     * using ZipArchive, then cleans up the temporary file.
     *
     * @param string $zippedContent Raw ZIP file content as binary string
     *
     * @return array<0|string, string|false> Associative array mapping file names to their content.
     *                                       Keys are file names from the ZIP archive,
     *                                       values are the file contents (or false on read error).
     * @throws RuntimeException If the ZIP archive cannot be opened
     */
    public function extractFilesFromString(string $zippedContent): array
    {
        // save content into temp file
        $tempFile = $this->createTmpFile();
        file_put_contents($tempFile, $zippedContent);

        $zip = new ZipArchive();
        if (true !== $zip->open($tempFile)) {
            throw new RuntimeException('Zip archive was not opened.');
        }

        // Read zipped order data items.
        $fileContentItems = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileContentItems[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }

        // Close zip.
        $zip->close();
        // Remove temporary file.
        unlink($tempFile);

        return $fileContentItems;
    }

    /**
     * Decompress gzip-compressed data from a Buffer to another Buffer.
     *
     * Uses PHP's zlib.inflate filter to decompress data in streaming mode,
     * which is memory-efficient for large data sets. Skips the first 2 bytes
     * of the gzip header before inflation.
     *
     * @param Buffer $compressed Buffer containing the gzip-compressed data
     * @param Buffer $uncompressed Buffer to write the decompressed data to
     *
     * @return void
     */
    public function uncompress(Buffer $compressed, Buffer $uncompressed): void
    {
        // Skip metadata.
        $compressed->fseek(2);

        $compressed->filterAppend('zlib.inflate', STREAM_FILTER_READ);

        while (!$compressed->eof()) {
            $string = $compressed->read();
            $uncompressed->write($string);
        }
        $uncompressed->rewind();
    }

    /**
     * Compress data using gzip compression.
     *
     * Uses PHP's gzcompress() function (zlib format, not gzip format)
     * to compress the input data. This is used before sending order
     * data to EBICS banks that expect compressed payloads.
     *
     * @param string $uncompressed The raw data to compress
     *
     * @return string The gzip-compressed data as binary string
     * @throws RuntimeException If compression fails
     */
    public function compress(string $uncompressed): string
    {
        if (!($compressed = gzcompress($uncompressed))) {
            throw new RuntimeException('Data can not be compressed.');
        }

        return $compressed;
    }
}
