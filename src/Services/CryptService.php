<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\Crypt\RSAInterface;
use EbicsApi\Ebics\Contracts\SignatureInterface;
use EbicsApi\Ebics\Exceptions\EbicsException;
use EbicsApi\Ebics\Factories\Crypt\AESFactory;
use EbicsApi\Ebics\Factories\Crypt\RSAFactory;
use EbicsApi\Ebics\Models\Buffer;
use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Crypt\KeyPair;
use EbicsApi\Ebics\Models\Crypt\RSA;
use EbicsApi\Ebics\Models\Keyring;
use LogicException;
use RuntimeException;

/**
 * CryptService provides cryptographic operations for EBICS protocol implementation.
 *
 * This service handles all encryption, decryption, hashing, signing, and key management
 * operations required by the EBICS (Electronic Banking Internet Communication Standard)
 * protocol. It supports multiple signature versions (A5, A6) and uses AES-128-CBC
 * for data encryption.
 *
 * Key responsibilities:
 * - Hash calculation (SHA-256 and other algorithms)
 * - Order data decryption using transaction keys
 * - Data encryption/decryption with AES-128-CBC
 * - RSA-based signing and encryption
 * - Key pair generation and management
 * - Public key digest calculation
 * - Certificate fingerprint generation
 * - Nonce and transaction key generation
 * - Order ID generation
 * - Private key validation and password management
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal This class is for internal use and may change without notice.
 */
final class CryptService
{
    /**
     * Factory for creating RSA crypt instances.
     *
     * @var RSAFactory
     */
    private RSAFactory $rsaFactory;

    /**
     * Factory for creating AES crypt instances.
     *
     * @var AESFactory
     */
    private AESFactory $aesFactory;

    /**
     * Service for generating random values.
     *
     * @var RandomService
     */
    private RandomService $randomService;

    /**
     * CryptService constructor.
     *
     * @param RSAFactory $rsaFactory Factory for creating RSA instances
     * @param AESFactory $aesFactory Factory for creating AES instances
     * @param RandomService $randomService Service for generating random values
     */
    public function __construct(RSAFactory $rsaFactory, AESFactory $aesFactory, RandomService $randomService)
    {
        $this->rsaFactory = $rsaFactory;
        $this->aesFactory = $aesFactory;
        $this->randomService = $randomService;
    }

    /**
     * Calculate hash of the given text using specified algorithm.
     *
     * @param string $text The input text to hash
     * @param string $algorithm The hashing algorithm to use (default: 'sha256')
     * @param bool $binary Whether to return raw binary data (true) or lowercase hexits (false)
     *
     * @return string The calculated hash
     */
    public function hash(string $text, string $algorithm = 'sha256', bool $binary = true): string
    {
        return hash($algorithm, $text, $binary);
    }

    /**
     * Decrypt encrypted OrderData using the user's signature E (encryption key).
     *
     * This method decrypts transaction data that was encrypted by the bank using the client's
     * public encryption key. It uses RSA decryption with the private key to recover
     * the transaction key, then uses AES decryption to recover the actual order data.
     *
     * @param Keyring $keyring The keyring containing the user's encryption signature
     * @param Buffer $orderDataEncrypted Buffer to store the decrypted order data
     * @param Buffer $orderDataCompressed Buffer containing the compressed encrypted data
     * @param string $transactionKey The encrypted transaction key to decrypt
     *
     * @return void
     * @throws RuntimeException If signature E is not set in the keyring
     * @throws EbicsException If decryption fails
     */
    public function decryptOrderDataCompressed(
        Keyring $keyring,
        Buffer $orderDataEncrypted,
        Buffer $orderDataCompressed,
        string $transactionKey
    ): void {
        if (!($signatureE = $keyring->getUserSignatureE())) {
            throw new RuntimeException('Signature E is not set.');
        }

        $rsa = $this->rsaFactory->createPrivate($signatureE->getPrivateKey(), $keyring->getPassword());
        $transactionKeyDecrypted = $rsa->decrypt($transactionKey);

        $this->decryptByKey($transactionKeyDecrypted, $orderDataEncrypted, $orderDataCompressed);
    }

    /**
     * Decrypt data using AES-128-CBC algorithm with a given key.
     *
     * This method performs AES-128-CBC decryption with OPENSSL_RAW_DATA and OPENSSL_ZERO_PADDING
     * options. It's typically used to decrypt order data after the transaction key has been
     * recovered.
     *
     * @param string $key The decryption key (16 bytes for AES-128)
     * @param Buffer $encrypted Buffer containing the encrypted data
     * @param Buffer $plaintext Buffer to store the decrypted plaintext
     *
     * @return void
     */
    public function decryptByKey(string $key, Buffer $encrypted, Buffer $plaintext): void
    {
        $aes = $this->aesFactory->create();
        $aes->setKeyLength(128);
        $aes->setKey($key);
        // Force openssl_options.
        $aes->setOpenSSLOptions(OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        $aes->decryptBuffer($encrypted, $plaintext);
    }

    /**
     * Encrypt data using AES-128-CBC algorithm with a given key.
     *
     * This method performs AES-128-CBC encryption with OPENSSL_RAW_DATA and OPENSSL_NO_PADDING
     * options. It's typically used to encrypt order data before sending to the bank.
     *
     * @param string $key The encryption key (16 bytes for AES-128)
     * @param string $data The plaintext data to encrypt
     *
     * @return string The encrypted data as binary string
     */
    public function encryptByKey(string $key, string $data): string
    {
        $aes = $this->aesFactory->create();
        $aes->setKeyLength(128);
        $aes->setKey($key);
        $aes->setOpenSSLOptions(OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
        $encrypted = $aes->encrypt($data);

        return $encrypted;
    }

    /**
     * Encrypt/sign data using RSA private key.
     *
     * This method signs or encrypts data using the user's private key. The signing method
     * depends on the signature version:
     * - A_VERSION6: Uses RSA-PSS with SHA-256 hash and MGF
     * - A_VERSION5: Uses RSA encryption with SHA-256 digest prefix
     *
     * @param Key $privateKey The private key for signing
     * @param string $password The password to decrypt the private key
     * @param string $version The signature version (e.g., SignatureInterface::A_VERSION5, A_VERSION6)
     * @param string $data The data to sign/encrypt
     *
     * @return string The signed/encrypted data as binary string
     */
    public function encrypt(
        Key $privateKey,
        string $password,
        string $version,
        string $data
    ): string {
        switch ($version) {
            case SignatureInterface::A_VERSION6:
                $rsa = $this->rsaFactory->createPrivate($privateKey, $password);
                $rsa->setHash('sha256');
                $rsa->setMGFHash('sha256');

                $encrypt = $rsa->sign($data);
                break;

            case SignatureInterface::A_VERSION5:
            default:
                $digestToSignBin = $this->filter($data);

                $rsa = $this->rsaFactory->createPrivate($privateKey, $password);

                $encrypt = $this->encryptByRsa($rsa, $digestToSignBin);
        }

        return $encrypt;
    }

    /**
     * Sign data using RSA private key.
     *
     * This method creates a digital signature for the given data using the user's private key.
     * The signing method depends on the signature version:
     * - A_VERSION5: Uses EMSA-PKCS1-v1_5 encoding with SHA-256
     * - A_VERSION6: Uses EMSA-PSS encoding with SHA-256 hash and MGF (with verification)
     *
     * @param Key $privateKey The private key for signing
     * @param string $password The password to decrypt the private key
     * @param string $version The signature version (e.g., SignatureInterface::A_VERSION5, A_VERSION6)
     * @param string $data The data to sign
     *
     * @return string The digital signature as binary string
     * @throws LogicException If signature version is not supported or PSS verification fails
     */
    public function sign(
        Key $privateKey,
        string $password,
        string $version,
        string $data
    ): string {
        switch ($version) {
            case SignatureInterface::A_VERSION5:
                $rsa = $this->rsaFactory->createPrivate($privateKey, $password);
                $rsa->setHash('sha256');
                $sign = $rsa->emsaPkcs1V15Encode($data);
                break;
            case SignatureInterface::A_VERSION6:
                $rsa = $this->rsaFactory->createPrivate($privateKey, $password);
                $rsa->setHash('sha256');
                $rsa->setMGFHash('sha256');
                $sign = $rsa->emsaPssEncode($data);
                if (!$rsa->emsaPssVerify($data, $sign)) {
                    throw new LogicException('Sign verification failed');
                }
                break;
            default:
                throw new LogicException(sprintf('Algorithm type for Version %s not supported', $version));
        }

        return $sign;
    }

    /**
     * Encrypt transaction key using RSA public key.
     *
     * This method encrypts a transaction key (session key) using the bank's public encryption key.
     * The encrypted transaction key is then sent to the bank, which decrypts it with its private key
     * to establish a secure communication channel.
     *
     * @param Key $publicKey The public key for encryption
     * @param string $transactionKey The transaction key to encrypt (typically 16 random bytes)
     *
     * @return string The encrypted transaction key as binary string
     */
    public function encryptTransactionKey(Key $publicKey, string $transactionKey): string
    {
        return $this->encryptByRsaPublicKey($publicKey, $transactionKey);
    }

    /**
     * Encrypt data using RSA private key.
     *
     * This internal helper method performs RSA encryption with a private key instance.
     *
     * @param RSAInterface $rsa The RSA instance configured with the private key
     * @param string $data The data to encrypt
     *
     * @return string The encrypted data
     * @throws RuntimeException If encryption fails
     */
    private function encryptByRsa(RSAInterface $rsa, string $data): string
    {
        if (!($encrypted = $rsa->encrypt($data))) {
            throw new RuntimeException('Incorrect encryption.');
        }

        return $encrypted;
    }

    /**
     * Encrypt data using RSA public key.
     *
     * This internal helper method performs RSA encryption with a public key.
     *
     * @param Key $publicKey The public key for encryption
     * @param string $data The data to encrypt
     *
     * @return string The encrypted data
     * @throws RuntimeException If encryption fails
     */
    private function encryptByRsaPublicKey(Key $publicKey, string $data): string
    {
        $rsa = $this->rsaFactory->createPublic($publicKey);

        if (!($encrypted = $rsa->encrypt($data))) {
            throw new RuntimeException('Incorrect encryption.');
        }

        return $encrypted;
    }

    /**
     * Generate an RSA key pair (public and private keys).
     *
     * Creates a new RSA key pair with the specified algorithm and key length.
     * The private key is encrypted with the provided password.
     *
     * @param string $password The password to encrypt the private key
     * @param string $algorithm The hash algorithm to use (default: 'sha256')
     * @param int $length The key length in bits (default: 2048, minimum recommended: 2048)
     *
     * @return KeyPair The generated key pair containing public key, private key, and password
     */
    public function generateKeyPair(
        string $password,
        string $algorithm = 'sha256',
        int $length = 2048
    ): KeyPair {
        $rsa = $this->rsaFactory->create(RSA::PRIVATE_FORMAT_PKCS1);
        $rsa->setHash($algorithm);
        $rsa->setPassword($password);

        return $rsa->createKey($length);
    }

    /**
     * Filter hash by adding SHA-256 ASN.1 prefix.
     *
     * This internal method prepares the hash for RSA signing by adding
     * the proper ASN.1 prefix for SHA-256 algorithm. This is required
     * for RSA PKCS#1 v1.5 signature scheme.
     *
     * @param string $hash The raw hash value to prefix
     *
     * @return string The prefixed hash ready for RSA operation
     */
    private function filter(string $hash): string
    {
        $RSA_SHA256prefix = [
            0x30,
            0x31,
            0x30,
            0x0D,
            0x06,
            0x09,
            0x60,
            0x86,
            0x48,
            0x01,
            0x65,
            0x03,
            0x04,
            0x02,
            0x01,
            0x05,
            0x00,
            0x04,
            0x20,
        ];
        $unpHash = $this->binToArray($hash);
        $signedInfoDigest = array_values($unpHash);
        $digestToSign = [];
        $this->systemArrayCopy($RSA_SHA256prefix, 0, $digestToSign, 0, count($RSA_SHA256prefix));
        $this->systemArrayCopy($signedInfoDigest, 0, $digestToSign, count($RSA_SHA256prefix), count($signedInfoDigest));

        return $this->arrayToBin($digestToSign);
    }

    /**
     * Internal helper method that mimics Java's System.arraycopy.
     *
     * Copies elements from source array to destination array with specified offsets and length.
     *
     * @param array<int, int> $a Source array
     * @param int $c Starting position in source array
     * @param array<int, int> $b Destination array (passed by reference)
     * @param int $d Starting position in destination array
     * @param int $length Number of elements to copy
     */
    private function systemArrayCopy(
        array $a,
        int $c,
        array &$b,
        int $d,
        int $length
    ): void {
        for ($i = 0; $i < $length; ++$i) {
            $b[$i + $d] = $a[$i + $c];
        }
    }

    /**
     * Pack array of byte values to a binary string.
     *
     * Converts an array of integer byte values (0-255) to a binary string.
     *
     * @param array<int, int> $bytes Array of byte values
     *
     * @return string Binary string representation
     */
    private function arrayToBin(
        array $bytes
    ): string {
        return call_user_func_array('pack', array_merge(['c*'], $bytes));
    }

    /**
     * Convert a binary string to an array of byte values.
     *
     * Each character in the binary string is converted to its ASCII value (0-255).
     *
     * @param string $bytes Binary string to convert
     *
     * @return array<int, int> Array of byte values
     * @throws RuntimeException If conversion fails
     */
    public function binToArray(
        string $bytes
    ): array {
        $result = unpack('C*', $bytes);
        if (false === $result) {
            throw new RuntimeException('Can not convert bytes to array.');
        }

        return $result;
    }

    /**
     * Calculate the digest of a public key (modulus and exponent).
     *
     * Extracts the modulus and exponent from the public key, formats them,
     * and calculates a hash digest. This is used for key identification
     * and verification purposes.
     *
     * @param SignatureInterface $signature The signature containing the public key
     * @param string $algorithm The hash algorithm to use (default: 'sha256')
     *
     * @return string The calculated public key digest as binary data
     */
    public function calculatePublicKeyDigest(
        SignatureInterface $signature,
        string $algorithm = 'sha256'
    ): string {
        $rsa = $this->rsaFactory->createPublic($signature->getPublicKey());

        $exponent = $rsa->getExponent()->toHex(true);
        $modulus = $rsa->getModulus()->toHex(true);

        $key = $this->calculateKey($exponent, $modulus);

        return $this->hash($key, $algorithm);
    }

    /**
     * Create a formatted key string from exponent and modulus.
     *
     * Removes leading zeros from both values and combines them with a space separator.
     * This format is used for key identification and digest calculations.
     *
     * @param string $exponent The hex-encoded exponent value
     * @param string $modulus The hex-encoded modulus value
     *
     * @return string The formatted key string (e.g., "010001 C4...")
     */
    public function calculateKey(
        string $exponent,
        string $modulus
    ): string {
        // Remove leading 0.
        $exponent = ltrim($exponent, '0');
        $modulus = ltrim($modulus, '0');

        return sprintf('%s %s', $exponent, $modulus);
    }

    /**
     * Calculate the fingerprint of an X.509 certificate.
     *
     * Generates a hash fingerprint of the certificate content for identification
     * and verification purposes.
     *
     * @param string $certContent The PEM or DER encoded certificate content
     * @param string $algorithm The hash algorithm to use (default: 'sha256')
     * @param bool $rawOutput If true, returns raw binary data; if false, returns hex string
     *
     * @return string The certificate fingerprint
     * @throws RuntimeException If the certificate fingerprint cannot be calculated
     */
    public function calculateCertificateFingerprint(
        string $certContent,
        string $algorithm = 'sha256',
        bool $rawOutput = true
    ): string {
        $fingerprint = openssl_x509_fingerprint($certContent, $algorithm, $rawOutput);
        if (false === $fingerprint) {
            throw new RuntimeException('Can not calculate fingerprint for certificate.');
        }

        return $fingerprint;
    }

    /**
     * Generate a cryptographic nonce (number used once).
     *
     * Creates a random 32-character hexadecimal string used for preventing
     * replay attacks in EBICS protocol communications.
     *
     * @return string A 32-character uppercase hexadecimal string
     */
    public function generateNonce(): string
    {
        return $this->randomService->hex(32);
    }

    /**
     * Generate a random transaction key.
     *
     * Creates 16 bytes of cryptographically secure random data to be used
     * as a symmetric key for AES-128 encryption of order data.
     *
     * @return string A 16-byte random string
     */
    public function generateTransactionKey(): string
    {
        return $this->randomService->bytes(16);
    }

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
    public function decomposePublicKey(Key $publicKey): array
    {
        $rsa = $this->rsaFactory->createPublic($publicKey);

        return [
            'e' => $rsa->getExponent()->toBytes(),
            'm' => $rsa->getModulus()->toBytes(),
        ];
    }

    /**
     * Generate a random order ID following EBICS format.
     *
     * Creates a 4-character order ID where the first character is a letter (A-Z)
     * and the remaining 3 characters are alphanumeric (0-9, A-Z).
     * Format: [A-Z][0-9A-Z]{3} (e.g., "A000", "Z9ZZ", "M123")
     *
     * @return string A random 4-character order ID
     */
    public function generateOrderId(): string
    {
        $first = chr(rand(65, 90));
        $num = rand(0, pow(36, 3) - 1);
        $suffix = strtoupper(base_convert((string)$num, 10, 36));
        $suffix = str_pad($suffix, 3, '0', STR_PAD_LEFT);

        return $first . $suffix;
    }

    /**
     * Validate that a private key can be loaded with the given password.
     *
     * Attempts to create an RSA instance from the private key using the provided password.
     * Returns true if successful, false if the key cannot be loaded (e.g., wrong password
     * or corrupted key).
     *
     * @param Key $privateKey The private key to validate
     * @param string $password The password to test
     *
     * @return bool True if the key is valid and can be loaded, false otherwise
     */
    public function checkPrivateKey(Key $privateKey, string $password): bool
    {
        try {
            $this->rsaFactory->createPrivate($privateKey, $password);

            return true;
        } catch (LogicException $exception) {
            return false;
        }
    }

    /**
     * Change the password for a private key in a key pair.
     *
     * Re-encrypts the private key with a new password while keeping the public key unchanged.
     * This is useful for key rotation or password updates without regenerating the key pair.
     *
     * @param KeyPair $keyPair The key pair containing the private key to update
     * @param string $oldPassword The current password for the private key
     * @param string $newPassword The new password to encrypt the private key with
     *
     * @return KeyPair A new key pair with the re-encrypted private key
     */
    public function changePrivateKeyPassword(KeyPair $keyPair, string $oldPassword, string $newPassword): KeyPair
    {
        $rsa = $this->rsaFactory->create($keyPair->getPrivateKey()->getType());

        return $rsa->changePassword(
            $keyPair,
            $oldPassword,
            $newPassword
        );
    }
}
