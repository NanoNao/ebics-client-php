<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\ZipServiceInterface;
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
final class ZipService implements ZipServiceInterface
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

    public function uncompressBuffer(Buffer $compressed, Buffer $uncompressed): void
    {
        $compressed->fseek(2);

        $context = inflate_init(ZLIB_ENCODING_RAW);
        if (false === $context) {
            throw new RuntimeException('Failed to initialize inflate context.');
        }
        while (!$compressed->eof()) {
            $output = inflate_add($context, $compressed->read());
            if (false === $output) {
                throw new RuntimeException('Failed to inflate data.');
            }
            if ('' !== $output) {
                $uncompressed->write($output);
            }
        }
        inflate_add($context, '');
        $uncompressed->rewind();
    }

    public function uncompress(string $compressed): string
    {
        if (false === ($result = gzuncompress($compressed))) {
            throw new RuntimeException('Data can not be uncompressed.');
        }

        return $result;
    }

    public function compress(string $uncompressed): string
    {
        if (!($compressed = gzcompress($uncompressed))) {
            throw new RuntimeException('Data can not be compressed.');
        }

        return $compressed;
    }

    public function compressBuffer(Buffer $uncompressed, Buffer $compressed): void
    {
        $context = deflate_init(ZLIB_ENCODING_DEFLATE);
        if (false === $context) {
            throw new RuntimeException('Failed to initialize deflate context.');
        }
        while (!$uncompressed->eof()) {
            $output = deflate_add($context, $uncompressed->read());
            if (false === $output) {
                throw new RuntimeException('Failed to deflate data.');
            }
            if ('' !== $output) {
                $compressed->write($output);
            }
        }
        $output = deflate_add($context, '', ZLIB_FINISH);
        if (false !== $output && '' !== $output) {
            $compressed->write($output);
        }
        $compressed->rewind();
    }
}
