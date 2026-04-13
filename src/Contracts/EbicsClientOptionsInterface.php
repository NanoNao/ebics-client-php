<?php

namespace EbicsApi\Ebics\Contracts;

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
     *
     * @return HttpClientInterface|null
     */
    public function getHttpClient(): ?HttpClientInterface;

    /**
     * Get the PSR-3 compatible logger instance.
     *
     * @return LoggerInterface|null
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
    public function getSchemaDir(): string;

    /**
     * Get the filename for buffer-based operations.
     */
    public function getBufferFilename(): string;
}
