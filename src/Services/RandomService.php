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
     * Generate a random uppercase hexadecimal string.
     *
     * The first character is never `0` to avoid leading-zero issues.
     *
     * @param int $length Number of characters to generate.
     *
     * @return string Random hex string (e.g. "3A7F0E1C").
     */
    public function hex(int $length): string
    {
        $characters = '0123456789ABCDEF';
        $randomHex = $this->random($characters, $length);

        return $randomHex;
    }

    /**
     * Generate a random digit string.
     *
     * The first character is never `0`.
     *
     * @param int $length Number of digits to generate.
     *
     * @return string Random digit string.
     */
    public function digits(int $length): string
    {
        $characters = '0123456789';
        $randomDigits = $this->random($characters, $length);

        return $randomDigits;
    }

    /**
     * Generate cryptographically secure random bytes.
     *
     * Uses PHP's `random_bytes()` internally.
     *
     * @param int $length Number of bytes to generate.
     *
     * @return string Raw binary string.
     * @throws LogicException If length is less than 1.
     */
    public function bytes(int $length): string
    {
        if ($length < 1) {
            throw new LogicException('Minimal length is 1');
        }
        return random_bytes($length);
    }

    /**
     * Generate random characters where first character not 0.
     *
     * @param string $characters
     * @param int $length
     *
     * @return string
     */
    private function random(string $characters, int $length): string
    {
        $charactersLength = strlen($characters);

        $random = '';

        // Avoid set 0 as first character.
        $random .= $characters[rand(1, $charactersLength - 1)];

        // Generate other characters randomly.
        for ($i = 1; $i < $length; $i++) {
            $random .= $characters[rand(0, $charactersLength - 1)];
        }

        return $random;
    }

    /**
     * Generate a unique ID prefixed with the current date-time.
     *
     * Format: `YYYYMMHisv` + `uniqid()`, truncated to 35 characters.
     *
     * @param string|null $prefix Optional prefix to prepend to the unique ID.
     *
     * @return string Unique identifier string.
     */
    public function uniqueIdWithDate(?string $prefix = null): string
    {
        $now = new DateTime();

        $uniqid = $prefix . uniqid($now->format('YmdHisv'));
        return substr($uniqid, 0, 35);
    }
}
