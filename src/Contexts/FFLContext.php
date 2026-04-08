<?php

namespace EbicsApi\Ebics\Contexts;

use EbicsApi\Ebics\Contracts\OrderContextInterface;

/**
 * Business transactions & formats.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class FFLContext implements OrderContextInterface
{
    private string $fileFormat;
    /** @var array<string, string> */
    private array $parameters = [];
    private ?string $countryCode = null;

    public function setFileFormat(string $fileFormat): self
    {
        $this->fileFormat = $fileFormat;

        return $this;
    }

    public function getFileFormat(): string
    {
        return $this->fileFormat;
    }

    public function setParameter(string $name, string $value): self
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    /** @return array<string, string> */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }
}
