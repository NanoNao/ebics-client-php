<?php

namespace EbicsApi\Ebics\Contexts;

/**
 * Business transactions & formats.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Geoffroy de Corbiac
 */
abstract class BTFContext extends ServiceContext
{
    /** @var array<string, string> */
    private array $parameters = [];

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
}
