<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Exceptions\EbicsException;
use EbicsApi\Ebics\Models\Buffer;
use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Crypt\KeyPair;
use EbicsApi\Ebics\Models\Keyring;
use RuntimeException;

/**
 * Crypt Service interface.
 *
 * Provides all cryptographic operations required by the EBICS protocol,
 * including hashing, encryption/decryption, signing, and key management.
 *
 * EBICS Protocol Context:
 * The CryptService is the core cryptographic engine used throughout the
 * EBICS client library. It supports:
 *
 * - **Hashing**: SHA-256 and other algorithms for digest computation
 * - **AES-128-CBC**: Symmetric encryption for order data using transaction keys
 * - **RSA**: Asymmetric operations (sign, encrypt, decrypt) with key sizes
 *   from 1024 to 4096 bits, supporting both PKCS#1 v1.5 and PSS padding
 * - **Key Management**: Generation, validation, and password management
 *   for RSA key pairs used in EBICS signatures
 *
 * The service abstracts the underlying cryptographic libraries (phpseclib)
 * and provides a consistent API tailored to EBICS protocol requirements.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface CryptServiceInterface
{
    /**
     * Compute a cryptographic hash of the given text.
     *
     * @param string $text The input data to hash
     * @param string $algorithm Hash algorithm (default: 'sha256')
     * @param bool $binary Whether to return raw binary (true) or hex string (false)
     *
     * @return string The hash output (binary or hex, depending on $binary)
     */
    public function hash(string $text, string $algorithm = 'sha256', bool $binary = true): string;

    /**
     * Decrypt compressed order data using a transaction key.
     *
     * This is the primary decryption method for EBICS download and
     * initialization responses. It performs:
     * 1. RSA decryption of the transaction key with the user's E private key
     * 2. AES-128-CBC decryption of the order data with the transaction key
     *
     * @param Keyring $keyring The keyring containing the user's E signature
     * @param Buffer $orderDataEncrypted The encrypted order data buffer (input)
     * @param Buffer $orderDataCompressed The decrypted order data buffer (output)
     * @param string $transactionKey The RSA-encrypted transaction key
     *
     * @return void
     *
     * @throws EbicsException If decryption fails
     */
    public function decryptOrderDataCompressed(
        Keyring $keyring,
        Buffer $orderDataEncrypted,
        Buffer $orderDataCompressed,
        string $transactionKey
    ): void;

    /**
     * Decrypt a buffer using a symmetric key (AES-128-CBC).
     *
     * @param string $key The AES decryption key
     * @param Buffer $encrypted The encrypted data buffer (input, will be modified)
     * @param Buffer $plaintext The decrypted data buffer (output)
     *
     * @return void
     *
     * @throws EbicsException If decryption fails
     */
    public function decryptByKey(string $key, Buffer $encrypted, Buffer $plaintext): void;

    /**
     * Encrypt data using a symmetric key (AES-128-CBC).
     *
     * @param string $key The AES encryption key
     * @param string $data The plaintext data to encrypt
     *
     * @return string The base64-encoded encrypted data
     *
     * @throws EbicsException If encryption fails
     */
    public function encryptByKey(string $key, string $data): string;

    /**
     * Encrypt data using RSA public key encryption.
     *
     * Used for encrypting transaction keys and other small data payloads
     * that need to be securely transmitted to the bank.
     *
     * @param Key $publicKey The RSA public key for encryption
     * @param string $transactionKey The AES transaction key to encrypt
     *
     * @return string The base64-encoded RSA-encrypted data
     *
     * @throws EbicsException If encryption fails
     */
    public function encryptTransactionKey(Key $publicKey, string $transactionKey): string;

    /**
     * Sign data using RSA private key with PKCS#1 v1.5 or PSS encoding.
     *
     * Creates a digital signature for the given data using the user's private key.
     * The signing method depends on the signature version:
     * - A_VERSION5 (A005): Uses EMSA-PKCS1-v1_5 encoding with SHA-256
     * - A_VERSION6 (A006): Uses EMSA-PSS encoding with SHA-256 hash and MGF
     *
     * @param Key $privateKey The private key for signing
     * @param string $password The password to decrypt the private key
     * @param string $version The signature version (e.g., SignatureInterface::A_VERSION5, A_VERSION6)
     * @param string $data The data to sign
     *
     * @return string The digital signature as binary string
     *
     * @throws EbicsException If signature version is not supported or PSS verification fails
     */
    public function sign(
        Key $privateKey,
        string $password,
        string $version,
        string $data
    ): string;

    /**
     * Encrypt/sign data using RSA private key.
     *
     * Performs RSA encryption or signing using the provided private key.
     * The operation depends on the signature version:
     * - A005: RSA-PKCS#1 v1.5 encryption
     * - A006: RSA-PSS signing with SHA-256
     *
     * @param Key $privateKey The private key for signing/encryption
     * @param string $password The password to decrypt the private key
     * @param string $version The signature version (e.g., SignatureInterface::A_VERSION5, A_VERSION6)
     * @param string $data The data to sign/encrypt
     *
     * @return string The signed/encrypted data as binary string
     *
     * @throws EbicsException If the operation fails
     */
    public function encrypt(
        Key $privateKey,
        string $password,
        string $version,
        string $data
    ): string;

    /**
     * Generate an RSA key pair for EBICS signatures.
     *
     * Creates a new RSA key pair with the specified algorithm and key length.
     * The private key is encrypted with the provided password.
     *
     * Supported key sizes: 1024, 2048, 3072, 4096 bits.
     * EBICS recommends minimum 2048 bits for production use.
     *
     * @param string $password Password to encrypt the private key
     * @param string $algorithm Hash algorithm to use (default: 'sha256')
     * @param int $length Key size in bits (default: 2048)
     *
     * @return KeyPair The generated key pair
     *
     * @throws EbicsException If key generation fails
     */
    public function generateKeyPair(
        string $password,
        string $algorithm = 'sha256',
        int $length = 2048
    ): KeyPair;

    /**
     * Convert a binary string to an array of byte values.
     *
     * Each character in the binary string is converted to its ASCII value (0-255).
     *
     * @param string $bytes Binary string to convert
     *
     * @return array<int, int> Array of byte values
     *
     * @throws EbicsException If conversion fails
     */
    public function binToArray(string $bytes): array;

    /**
     * Calculate the digest of a public key from a signature.
     *
     * Extracts the modulus and exponent from the signature's public key,
     * formats them, and calculates a hash digest. This is used for key
     * identification and verification purposes.
     *
     * @param SignatureInterface $signature The signature containing the public key
     * @param string $algorithm Hash algorithm (default: 'sha256')
     *
     * @return string The calculated public key digest as binary data
     */
    public function calculatePublicKeyDigest(SignatureInterface $signature, string $algorithm = 'sha256'): string;

    /**
     * Create a formatted key string from exponent and modulus.
     *
     * Removes leading zeros from both hex values and combines them with
     * a space separator. This format is used for key identification
     * and digest calculations.
     *
     * @param string $exponent The hex-encoded exponent value
     * @param string $modulus The hex-encoded modulus value
     *
     * @return string The formatted key string (e.g., "010001 C4...")
     */
    public function calculateKey(string $exponent, string $modulus): string;

    /**
     * Calculate the X.509 certificate fingerprint.
     *
     * Computes SHA-256 hash of the raw certificate bytes.
     *
     * @param string $certContent The PEM or DER-encoded certificate
     * @param string $algorithm Hash algorithm (default: 'sha256')
     * @param bool $rawOutput If true, returns raw binary data; if false, returns hex string
     *
     * @return string The certificate fingerprint
     * @throws RuntimeException If the certificate fingerprint cannot be calculated
     */
    public function calculateCertificateFingerprint(
        string $certContent,
        string $algorithm = 'sha256',
        bool $rawOutput = true
    ): string;

    /**
     * Generate a cryptographically secure random nonce.
     *
     * Nonces are used in EBICS requests to prevent replay attacks.
     *
     * @return string The base64-encoded nonce value
     */
    public function generateNonce(): string;

    /**
     * Generate a cryptographically secure random transaction key.
     *
     * Transaction keys are 128-bit AES keys used to encrypt order data
     * during transmission.
     *
     * @return string The raw binary transaction key (16 bytes)
     */
    public function generateTransactionKey(): string;

    /**
     * Extract modulus and exponent from an RSA public key.
     *
     * Decomposes an RSA public key into its constituent parts (modulus and exponent)
     * for use in key exchange or verification operations.
     *
     * @param Key $publicKey The RSA public key to decompose
     *
     * @return array{e: string, m: string} Associative array with 'e' (exponent) and 'm' (modulus) as bytes
     */
    public function decomposePublicKey(Key $publicKey): array;

    /**
     * Generate a unique order ID for EBICS upload orders.
     *
     * Order IDs must be unique per transaction to prevent duplicate
     * processing at the bank.
     *
     * @return string The generated order ID
     */
    public function generateOrderId(): string;

    /**
     * Verify that a private key can be decrypted with the given password.
     *
     * @param Key $privateKey The encrypted private key to check
     * @param string $password The password to attempt decryption with
     *
     * @return bool True if the password is correct, false otherwise
     */
    public function checkPrivateKey(Key $privateKey, string $password): bool;

    /**
     * Change the encryption password of a private key.
     *
     * Re-encrypts the private key with a new password without changing
     * the underlying cryptographic key material.
     *
     * @param KeyPair $keyPair The key pair containing the private key to re-encrypt
     * @param string $oldPassword The current encryption password
     * @param string $newPassword The new encryption password
     *
     * @return KeyPair A new KeyPair with the re-encrypted private key
     *
     * @throws EbicsException If password change fails
     */
    public function changePrivateKeyPassword(KeyPair $keyPair, string $oldPassword, string $newPassword): KeyPair;
}
