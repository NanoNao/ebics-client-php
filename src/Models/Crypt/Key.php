<?php

namespace EbicsApi\Ebics\Models\Crypt;

/**
 * Cryptographic key wrapper.
 *
 * Holds raw key material (PEM string or binary) and its type identifier
 * (e.g. `RSA::PRIVATE_FORMAT_PKCS1`, `RSA::PUBLIC_FORMAT_PKCS1`).
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class Key
{
    /**
     * @param mixed|string $key
     */
    public function __construct(
        private readonly mixed $key,
        private readonly int $type
    ) {
    }

    /**
     * @return mixed|string
     */
    public function getKey(): mixed
    {
        return $this->key;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
