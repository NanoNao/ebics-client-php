<?php

namespace EbicsApi\Ebics\Services;

use RuntimeException;
use ZipArchive;

/**
 * Extracts files from ZIP archives.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class ZipArchiveExtractor
{
    /**
     * Extract all files from a ZIP-compressed string.
     *
     * @param string $zippedContent Raw ZIP file content as binary string
     *
     * @return array<0|string, string|false> Associative array mapping file names to their content
     *
     * @throws RuntimeException If the ZIP archive cannot be opened
     */
    public function extractFilesFromString(string $zippedContent): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ebics-file');

        if (false === $tempFile) {
            throw new RuntimeException('Can not create temporary file.');
        }

        file_put_contents($tempFile, $zippedContent);

        $zip = new ZipArchive();
        if (true !== $zip->open($tempFile)) {
            throw new RuntimeException('Zip archive was not opened.');
        }

        $fileContentItems = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileContentItems[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }

        $zip->close();
        unlink($tempFile);

        return $fileContentItems;
    }
}
