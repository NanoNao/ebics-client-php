<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\Base64ServiceInterface;
use EbicsApi\Ebics\Models\Buffer;

/**
 * Base64 encoding/decoding service.
 *
 * Provides string-based and buffer-based base64 operations for EBICS
 * data transfer. The buffer variants process data in chunks for
 * memory-efficient processing of large payloads.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal This class is for internal use and may change without notice.
 */
final class Base64Service implements Base64ServiceInterface
{
    public function encode(string $data): string
    {
        return base64_encode($data);
    }

    public function encodeBuffer(Buffer $input, Buffer $output): void
    {
        $remainder = '';
        while (!$input->eof()) {
            $chunk = $remainder . $input->read();
            $chunkLen = strlen($chunk);
            $processLen = $chunkLen - ($chunkLen % 3);
            $output->write(base64_encode(substr($chunk, 0, $processLen)));
            $remainder = substr($chunk, $processLen);
        }
        if ('' !== $remainder) {
            $output->write(base64_encode($remainder));
        }
        $output->rewind();
    }

    public function decode(string $data): string
    {
        return base64_decode($data);
    }

    public function decodeBuffer(Buffer $input, Buffer $output): void
    {
        $remainder = '';
        while (!$input->eof()) {
            $chunk = $remainder . $input->read();
            $chunkLen = strlen($chunk);
            $processLen = $chunkLen - ($chunkLen % 4);
            $output->write(base64_decode(substr($chunk, 0, $processLen)));
            $remainder = substr($chunk, $processLen);
        }
        if ('' !== $remainder) {
            $output->write(base64_decode($remainder));
        }
        $output->rewind();
    }
}
