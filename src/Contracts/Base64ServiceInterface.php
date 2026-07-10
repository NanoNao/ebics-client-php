<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\Buffer;

/**
 * Base64 encoding/decoding service interface.
 *
 * Provides string-based and buffer-based (streaming) base64 operations
 * for EBICS data transfer. The buffer variants enable memory-efficient
 * processing of large payloads without loading them entirely into memory.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface Base64ServiceInterface
{
    /**
     * Encode data to base64 string.
     *
     * @param string $data Raw data to encode
     *
     * @return string Base64-encoded string
     */
    public function encode(string $data): string;

    /**
     * Encode data from one Buffer to another using streaming base64.
     *
     * @param Buffer $input Buffer containing raw data (input)
     * @param Buffer $output Buffer to write the base64-encoded data to (output)
     *
     * @return void
     */
    public function encodeBuffer(Buffer $input, Buffer $output): void;

    /**
     * Decode base64 string to raw data.
     *
     * @param string $data Base64-encoded string
     *
     * @return string Decoded raw data
     */
    public function decode(string $data): string;

    /**
     * Decode base64 data from one Buffer to another using streaming base64.
     *
     * @param Buffer $input Buffer containing base64-encoded data (input)
     * @param Buffer $output Buffer to write the decoded data to (output)
     *
     * @return void
     */
    public function decodeBuffer(Buffer $input, Buffer $output): void;
}
