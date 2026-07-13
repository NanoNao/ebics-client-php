<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\Buffer;
use RuntimeException;

/**
 * ZIP/Gzip Compression Service interface.
 *
 * Handles compression and decompression operations for EBICS order data,
 * supporting both ZIP archive extraction and gzip (zlib) streaming.
 *
 * EBICS Protocol Context:
 * EBICS banks commonly compress order data to reduce transmission size:
 * - **ZIP archives**: Used when banks send multiple files (e.g., account
 *   statements with multiple attachments, payment confirmation packages)
 * - **gzip/zlib**: Used for compressing individual order data payloads
 *   both in upload requests and download responses
 *
 * The compression handling differs between the two:
 * - ZIP: Requires temporary file extraction (PHP ZipArchive limitation)
 * - gzip: Supports streaming decompression via PHP stream filters for
 *   memory-efficient processing of large data
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface ZipServiceInterface
{
    /**
     * Extract all files from a ZIP-compressed string.
     *
     * Creates a temporary file, writes the ZIP content to it, extracts
     * all files using ZipArchive, then cleans up the temporary file.
     *
     * @param string $zippedContent Raw ZIP file content as binary string
     *
     * @return array<0|string, string|false> Associative array mapping file names
     *                                       to their content. Keys are file names
     *                                       from the ZIP archive, values are the
     *                                       file contents (or false on read error).
     *
     * @throws RuntimeException If the ZIP archive cannot be opened
     */
    public function extractFilesFromString(string $zippedContent): array;

    /**
     * Decompress gzip-compressed data from one Buffer to another.
     *
     * Uses PHP's zlib.inflate stream filter to decompress data in a
     * memory-efficient streaming mode. Skips the first 2 bytes of the
     * gzip header before inflation.
     *
     * @param Buffer $compressed Buffer containing the gzip-compressed data (input)
     * @param Buffer $uncompressed Buffer to write the decompressed data to (output)
     *
     * @return void
     */
    public function uncompressBuffer(Buffer $compressed, Buffer $uncompressed): void;

    /**
     * Decompress zlib-compressed data.
     *
     * Uses PHP's gzuncompress() function (zlib inflate format) to decompress
     * the input data received from EBICS banks.
     *
     * @param string $compressed The zlib-compressed data as binary string
     *
     * @return string The decompressed data
     *
     * @throws RuntimeException If decompression fails
     */
    public function uncompress(string $compressed): string;

    /**
     * Compress data using zlib compression.
     *
     * Uses PHP's gzcompress() function (zlib deflate format) to compress
     * the input data before sending to EBICS banks that expect compressed
     * order data payloads.
     *
     * @param string $uncompressed The raw data to compress
     *
     * @return string The zlib-compressed data as binary string
     *
     * @throws RuntimeException If compression fails
     */
    public function compress(string $uncompressed): string;

    /**
     * Compress data from one Buffer to another using streaming deflate.
     *
     * Uses PHP's deflate_init/deflate_add for memory-efficient streaming
     * compression. Produces zlib-compressed data compatible with both
     * uncompress() and uncompressBuffer().
     *
     * @param Buffer $uncompressed Buffer containing the raw data (input)
     * @param Buffer $compressed Buffer to write the compressed data to (output)
     *
     * @return void
     *
     * @throws RuntimeException If compression fails
     */
    public function compressBuffer(Buffer $uncompressed, Buffer $compressed): void;
}
