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
     * @var mixed|string
     */
    private $key;

    private int $type;

    /**
     * @param mixed|string $key
     * @param int $type
     */
    public function __construct($key, int $type)
    {
        $this->key = $key;
        $this->type = $type;
    }

    /**
     * @return mixed|string
     */
    public function getKey()
    {
        return $this->key;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
