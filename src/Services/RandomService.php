<?php

namespace EbicsApi\Ebics\Services;

use DateTime;
use LogicException;

/**
 * Random value generation for cryptographic operations.
 *
 * Provides hex strings, digit strings, raw random bytes, and unique IDs
 * with date-time prefixes. Used for nonces, transaction keys, and order IDs.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal
 */
final class RandomService
{
    /**
     * Generate a cryptographically secure random uppercase hexadecimal string.
     *
     * The first character is never `0`.
     *
     * @param int $length Number of characters to generate. Must be >= 1.
     *
     * @return string Random uppercase hex string (e.g. "3A7F0E1C").
     *
     * @throws LogicException If $length is less than 1.
     */
    public function hex(int $length): string
    {
        return $this->random('0123456789ABCDEF', $length);
    }

    /**
     * Generate a cryptographically secure random digit string.
     *
     * The first character is never `0`.
     *
     * @param int $length Number of digits to generate. Must be >= 1.
     *
     * @return string Random digit string (e.g. "48203917").
     *
     * @throws LogicException If $length is less than 1.
     */
    public function digits(int $length): string
    {
        return $this->random('0123456789', $length);
    }

    /**
     * Generate cryptographically secure random bytes.
     *
     * @param int $length Number of bytes to generate. Must be >= 1.
     *
     * @return string Raw binary string.
     *
     * @throws LogicException If $length is less than 1.
     */
    public function bytes(int $length): string
    {
        if ($length < 1) {
            throw new LogicException('Minimal length is 1.');
        }

        return random_bytes($length);
    }

    /**
     * Generate a unique ID prefixed with the current date-time.
     *
     * Format: `YmdHisv` + `uniqid()`, truncated to 35 characters.
     *
     * @param string|null $prefix Optional prefix to prepend to the unique ID.
     *
     * @return string Unique identifier string, at most 35 characters.
     */
    public function uniqueIdWithDate(?string $prefix = null): string
    {
        $now = new DateTime();

        return substr($prefix . uniqid($now->format('YmdHisv')), 0, 35);
    }

    /**
     * Generate a random string where the first character is never `0`.
     *
     * @param string $characters Pool of characters to draw from (must have at least 2 characters).
     *                           Index 0 is excluded from the first position.
     * @param int    $length     Number of characters to generate. Must be >= 1.
     *
     * @return string Random string drawn from $characters.
     */
    private function random(string $characters, int $length): string
    {
        if (strlen($characters) < 2) {
            throw new LogicException('Character pool must contain at least 2 characters.');
        }

        $lastIndex = strlen($characters) - 1;

        // First character must not be index 0 (e.g. '0' in a digit or hex string).
        $result = $characters[random_int(1, $lastIndex)];

        for ($i = 1; $i < $length; $i++) {
            $result .= $characters[random_int(0, $lastIndex)];
        }

        return $result;
    }
}
