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

    public function compress(string $uncompressed): string
    {
        if (!($compressed = gzcompress($uncompressed))) {
            throw new RuntimeException('Data can not be compressed.');
        }

        return $compressed;
    }
}
