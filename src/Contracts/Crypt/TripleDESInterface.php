<?php

namespace EbicsApi\Ebics\Contracts\Crypt;

/**
 * Triple DES (3DES) cryptographic interface.
 *
 * Provides encryption and decryption using the Triple Data Encryption
 * Standard algorithm with three-key operation (168-bit effective key
 * length).
 *
 * EBICS Protocol Context:
 * Triple DES is a legacy encryption algorithm that was used in earlier
 * versions of the EBICS protocol (pre-2.5) for encrypting order data
 * during transmission. Modern EBICS implementations should prefer AES
 * (Advanced Encryption Standard) via the {@see AESInterface} for new
 * deployments.
 *
 * Triple DES operates in CBC (Cipher Block Chaining) mode within EBICS,
 * requiring both a key and an initialization vector (IV). The algorithm
 * applies the DES cipher three times to each data block for enhanced
 * security over single DES.
 *
 * Note: DES requires that every eighth bit of the key be a parity bit;
 * however, this implementation ignores parity bits as they are not
 * relevant for EBICS security.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface TripleDESInterface
{
    /**
     * Set the encryption key.
     *
     * Keys can be of any length. Triple DES, itself, can use 128-bit
     * (e.g., strlen($key) == 16) or 192-bit (e.g., strlen($key) == 24)
     * keys. This function pads and truncates $key as appropriate.
     *
     * If the key is not explicitly set, it will be assumed to be all
     * null bytes.
     *
     * @param string $key The encryption key
     *
     * @return void
     */
    public function setKey(string $key);

    /**
     * Set the initialization vector (IV).
     *
     * The IV is not required when ECB mode is being used. If not
     * explicitly set, it will be assumed to be all zero bytes.
     *
     * @param string $iv The initialization vector
     *
     * @return void
     */
    public function setIV(string $iv);

    /**
     * Decrypt a ciphertext message.
     *
     * @param string $ciphertext The encrypted message to decrypt
     *
     * @return string The decrypted plaintext message
     */
    public function decrypt(string $ciphertext);

    /**
     * Encrypt a plaintext message.
     *
     * @param string $plaintext The message to encrypt
     *
     * @return string The encrypted ciphertext
     */
    public function encrypt(string $plaintext);
}
