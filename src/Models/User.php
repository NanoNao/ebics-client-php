<?php

namespace EbicsApi\Ebics\Models;

/**
 * EBICS user representation.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final readonly class User
{
    /**
     * Constructor.
     *
     * @param string $partnerId
     * @param string $userId
     */
    public function __construct(
        private string $partnerId,
        private string $userId
    ) {
    }

    /**
     * Getter for {partnerId}.
     *
     * @return string
     */
    public function getPartnerId(): string
    {
        return $this->partnerId;
    }

    /**
     * Getter for {userId}.
     *
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }
}
