<?php

namespace EbicsApi\Ebics\Models;

use EbicsApi\Ebics\Contracts\EbicsClientOptionsInterface;
use EbicsApi\Ebics\Contracts\HttpClientInterface;
use EbicsApi\Ebics\Contracts\LoggerInterface;
use EbicsApi\Ebics\Contracts\Processor\AESEncryptorInterface;
use EbicsApi\Ebics\Contracts\Processor\Base64EncoderInterface;
use EbicsApi\Ebics\Contracts\Processor\ZipCompressorInterface;

/**
 * Default implementation of EBICS client options.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class EbicsClientOptions implements EbicsClientOptionsInterface
{
    public ?HttpClientInterface $httpClient = null;
    public ?LoggerInterface $logger = null;

    /**
     * @var array<int, class-string<\EbicsApi\Ebics\Contracts\Crypt\RSAInterface>>|null
     */
    public ?array $rsaClassMap = null;

    public ?string $schemaDir = null;

    /**
     * @var array<int, mixed> cURL options (CURLOPT_* constants as keys)
     */
    public array $curlOptions = [];

    public ?Base64EncoderInterface $base64Pipe = null;
    public ?AESEncryptorInterface $aesPipe = null;
    public ?ZipCompressorInterface $gzipPipe = null;

    public function getHttpClient(): ?HttpClientInterface
    {
        return $this->httpClient;
    }

    public function setHttpClient(?HttpClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getRsaClassMap(): ?array
    {
        return $this->rsaClassMap;
    }

    /**
     * @param array<int, class-string<\EbicsApi\Ebics\Contracts\Crypt\RSAInterface>>|null $rsaClassMap
     */
    public function setRsaClassMap(?array $rsaClassMap): self
    {
        $this->rsaClassMap = $rsaClassMap;

        return $this;
    }

    public function getSchemaDir(): ?string
    {
        return $this->schemaDir;
    }

    public function setSchemaDir(?string $schemaDir): self
    {
        $this->schemaDir = $schemaDir;

        return $this;
    }

    /**
     * @return array<int, mixed> CURLOPT_* constants as keys
     */
    public function getCurlOptions(): array
    {
        return $this->curlOptions;
    }

    /**
     * @param array<int, mixed> $curlOptions CURLOPT_* constants as keys
     */
    public function setCurlOptions(array $curlOptions): self
    {
        $this->curlOptions = $curlOptions;

        return $this;
    }

    public function getBase64Encoder(): ?Base64EncoderInterface
    {
        return $this->base64Pipe;
    }

    public function setBase64Encoder(?Base64EncoderInterface $base64Pipe): self
    {
        $this->base64Pipe = $base64Pipe;

        return $this;
    }

    public function getAesEncryptor(): ?AESEncryptorInterface
    {
        return $this->aesPipe;
    }

    public function setAesEncryptor(?AESEncryptorInterface $aesPipe): self
    {
        $this->aesPipe = $aesPipe;

        return $this;
    }

    public function getZipCompressor(): ?ZipCompressorInterface
    {
        return $this->gzipPipe;
    }

    public function setZipCompressor(?ZipCompressorInterface $gzipPipe): self
    {
        $this->gzipPipe = $gzipPipe;

        return $this;
    }
}
