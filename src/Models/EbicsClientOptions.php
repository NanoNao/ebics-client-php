<?php

namespace EbicsApi\Ebics\Models;

use EbicsApi\Ebics\Contracts\EbicsClientOptionsInterface;
use EbicsApi\Ebics\Contracts\HttpClientInterface;
use EbicsApi\Ebics\Contracts\LoggerInterface;

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
    public string $bufferFilename = 'php://memory';

    /**
     * @var array<int, mixed> cURL options (CURLOPT_* constants as keys)
     */
    public array $curlOptions = [];

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

    public function getSchemaDir(): string
    {
        return $this->schemaDir;
    }

    public function setSchemaDir(string $schemaDir): self
    {
        $this->schemaDir = $schemaDir;

        return $this;
    }

    public function getBufferFilename(): string
    {
        return $this->bufferFilename;
    }

    public function setBufferFilename(string $bufferFilename): self
    {
        $this->bufferFilename = $bufferFilename;

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
}
