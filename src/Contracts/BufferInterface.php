<?php

namespace EbicsApi\Ebics\Contracts;

/**
 * Buffer interface.
 *
 * Provides a stream-based buffer for reading and writing data during
 * EBICS operations. This interface abstracts PHP stream operations
 * and is primarily used for handling large order data that may exceed
 * memory limits when processed as a single string.
 *
 * The buffer supports standard stream operations (open, close, read, write,
 * seek) as well as stream filter application for on-the-fly data
 * transformation (e.g., compression, encoding).
 *
 * EBICS Protocol Context:
 * Large order data (e.g., batch payment files) are often streamed rather
 * than loaded into memory. The buffer enables efficient handling of:
 * - Upload order data segmented into chunks
 * - Download order data reassembled from segments
 * - ZIP compression/decompression on the fly via stream filters
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface BufferInterface
{
    /**
     * Default read length in bytes for buffer read operations.
     */
    const DEFAULT_READ_LENGTH = 1024;

    /**
     * Open the buffer with the specified mode.
     *
     * Initializes the underlying stream resource for reading and/or writing.
     * Common modes: 'r' (read), 'w' (write), 'r+' (read/write).
     *
     * @param string $mode Stream open mode (same as PHP fopen modes)
     *
     * @return void
     */
    public function open(string $mode): void;

    /**
     * Close the buffer and release the underlying stream resource.
     *
     * @return void
     */
    public function close(): void;

    /**
     * Reset the stream pointer to the beginning of the buffer.
     *
     * @return void
     */
    public function rewind(): void;

    /**
     * Write a string to the buffer at the current position.
     *
     * @param string $string The data to write
     *
     * @return void
     */
    public function write(string $string): void;

    /**
     * Read data from the buffer at the current position.
     *
     * If no length is specified, reads up to DEFAULT_READ_LENGTH bytes.
     *
     * @param int|null $length Number of bytes to read (defaults to DEFAULT_READ_LENGTH)
     *
     * @return string The data read from the buffer
     */
    public function read(?int $length = null): string;

    /**
     * Read the entire buffer content from the current position to EOF.
     *
     * @return string The full remaining content of the buffer
     */
    public function readContent(): string;

    /**
     * Check if the stream pointer has reached the end of the buffer.
     *
     * @return bool True if the pointer is at EOF, false otherwise
     */
    public function eof(): bool;

    /**
     * Move the stream pointer to the specified byte offset.
     *
     * @param int $offset The byte offset to seek to
     *
     * @return int Returns 0 on success, or -1 on failure
     */
    public function fseek(int $offset): int;

    /**
     * Apply a stream filter to the buffer for data transformation.
     *
     * Filters can be used for on-the-fly compression (zlib), encoding
     * (convert.*), or custom data processing during read/write operations.
     *
     * @param string $filterName The PHP stream filter name (e.g., 'zlib.deflate')
     * @param int $mode Filter mode: STREAM_FILTER_READ, STREAM_FILTER_WRITE, or STREAM_FILTER_ALL
     *
     * @return void
     */
    public function filterAppend(string $filterName, int $mode): void;

    /**
     * Get the total length of the buffer content in bytes.
     *
     * @return int The byte length of the buffer content
     */
    public function length(): int;
}
