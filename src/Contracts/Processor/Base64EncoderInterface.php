<?php

namespace EbicsApi\Ebics\Contracts\Processor;

/**
 * Base64 encode/decode pipe interface.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface Base64EncoderInterface
{
    /**
     * Encode data to base64.
     */
    public function encode(string $data): string;

    /**
     * Decode base64 data.
     */
    public function decode(string $data): string;
}
