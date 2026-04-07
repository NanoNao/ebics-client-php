<?php

namespace EbicsApi\Ebics\Factories\Crypt;

use EbicsApi\Ebics\Contracts\Crypt\AESInterface;
use EbicsApi\Ebics\Models\Crypt\AES;

/**
 * AES factory for creating AES cryptographic instances.
 *
 * Produces `AESInterface` instances used for symmetric encryption/decryption
 * (typically AES-128-CBC) in EBICS data transfer pipelines.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class AESFactory
{
    /**
     * @return AESInterface
     */
    public function create(): AESInterface
    {
        return new AES();
    }
}
