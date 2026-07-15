<?php

namespace EbicsApi\Ebics\Contracts\Processor;

/**
 * Gzip compress/uncompress pipe interface.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface ZipCompressorInterface
{
    /**
     * Compress data with gzip.
     */
    public function compress(string $data): string;

    /**
     * Uncompress gzip data.
     */
    public function uncompress(string $data): string;
}
