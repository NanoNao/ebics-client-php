<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Contracts\Processor\AESEncryptorInterface;
use EbicsApi\Ebics\Contracts\Processor\Base64EncoderInterface;
use EbicsApi\Ebics\Contracts\Processor\ZipCompressorInterface;

/**
 * Options for configuring the EBICS client.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface EbicsClientOptionsInterface
{
    /**
     * Get the custom HTTP client implementation.
     */
    public function getHttpClient(): ?HttpClientInterface;

    /**
     * Get the PSR-3 compatible logger instance.
     */
    public function getLogger(): ?LoggerInterface;

    /**
     * Get the RSA class map for cryptographic implementation swapping.
     *
     * @return array<int, class-string<\EbicsApi\Ebics\Contracts\Crypt\RSAInterface>>|null
     */
    public function getRsaClassMap(): ?array;

    /**
     * Get the directory containing EBICS XSD schema files.
     */
    public function getSchemaDir(): ?string;

    /**
     * Get cURL options for CurlHttpClient.
     *
     * @return array<int, mixed> CURLOPT_* constants as keys
     */
    public function getCurlOptions(): array;

    /**
     * Get the base64 encoder for encoding/decoding.
     */
    public function getBase64Encoder(): ?Base64EncoderInterface;

    /**
     * Get the AES encryptor for encryption/decryption.
     */
    public function getAesEncryptor(): ?AESEncryptorInterface;

    /**
     * Get the zip compressor for compression/decompression.
     */
    public function getZipCompressor(): ?ZipCompressorInterface;
}
