<?php

namespace EbicsApi\Ebics\Services\Processor;

use EbicsApi\Ebics\Contracts\Processor\ZipCompressorInterface;
use RuntimeException;

/**
 * Gzip compress/uncompress pipe.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class ZipCompressor implements ZipCompressorInterface
{
    private const MAX_DATA_LENGTH = 10485760;

    public function compress(string $data): string
    {
        $length = strlen($data);

        if ($length > self::MAX_DATA_LENGTH) {
            throw new RuntimeException(sprintf(
                'Data exceeds maximum length of %d bytes, got %d bytes. Use buffered analog.',
                self::MAX_DATA_LENGTH,
                $length
            ));
        }

        $result = gzcompress($data);

        if (false === $result) {
            throw new RuntimeException(sprintf(
                'Failed to compress %d bytes of data with gzcompress().',
                $length
            ));
        }

        return $result;
    }

    public function uncompress(string $data): string
    {
        $length = strlen($data);

        if ($length > self::MAX_DATA_LENGTH) {
            throw new RuntimeException(sprintf(
                'Data exceeds maximum length of %d bytes, got %d bytes. Use buffered analog.',
                self::MAX_DATA_LENGTH,
                $length
            ));
        }

        if ($length < 2) {
            throw new RuntimeException(sprintf(
                'Invalid zlib data: need at least 2 bytes for header, got %d bytes.',
                $length
            ));
        }

        $cmf = ord($data[0]);
        $flg = ord($data[1]);

        // ZLIB header check: CMF must have CM=8 (deflate) in lower nibble,
        // and (CMF * 256 + FLG) must be divisible by 31.
        if (($cmf & 0x0F) !== 8 || ($cmf * 256 + $flg) % 31 !== 0) {
            throw new RuntimeException(sprintf(
                'Invalid zlib header: CMF=0x%02x, FLG=0x%02x (CM must be 8, checksum failed).',
                $cmf,
                $flg
            ));
        }

        $result = gzuncompress($data);

        if (false === $result) {
            throw new RuntimeException(sprintf(
                'Failed to uncompress %d bytes of zlib data with gzuncompress().',
                $length
            ));
        }

        return $result;
    }
}
