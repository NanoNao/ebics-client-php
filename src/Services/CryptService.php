<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\Crypt\RSAInterface;
use EbicsApi\Ebics\Contracts\CryptServiceInterface;
use EbicsApi\Ebics\Contracts\SignatureInterface;
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
final class CryptService implements CryptServiceInterface
{
    /**
     * CryptService constructor.
     *
     * @param RSAFactory $rsaFactory Factory for creating RSA instances
     * @param AESFactory $aesFactory Factory for creating AES instances
     * @param RandomService $randomService Service for generating random values
     */
    public function __construct(
        private readonly RSAFactory $rsaFactory,
        private readonly AESFactory $aesFactory,
        private readonly RandomService $randomService
    ) {
    }

    public function hash(string $text, string $algorithm = 'sha256', bool $binary = true): string
    {
        return hash($algorithm, $text, $binary);
    }

    public function decryptOrderDataCompressed(
        Keyring $keyring,
        Buffer $orderDataEncrypted,
        Buffer $orderDataCompressed,
        string $transactionKey
    ): void {
        if (!($signatureE = $keyring->getUserSignatureE())) {
            throw new RuntimeException('Signature E is not set.');
        }
        $privateKey = $signatureE->getPrivateKey();
        if ($privateKey === null) {
            throw new RuntimeException('Signature E private key is not set.');
        }
        $rsa = $this->rsaFactory->createPrivate($privateKey, $keyring->getPassword());
        $transactionKeyDecrypted = $rsa->decrypt($transactionKey);

        $this->decryptByKey($transactionKeyDecrypted, $orderDataEncrypted, $orderDataCompressed);
    }

    public function decryptByKey(string $key, Buffer $encrypted, Buffer $plaintext): void
    {
        $aes = $this->aesFactory->create();
        $aes->setKeyLength(128);
        $aes->setKey($key);
        // Force openssl_options.
        $aes->setOpenSSLOptions(OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        $aes->decryptBuffer($encrypted, $plaintext);
    }

    public function encryptByKey(string $key, string $data): string
    {
        $aes = $this->aesFactory->create();
        $aes->setKeyLength(128);
        $aes->setKey($key);
        $aes->setOpenSSLOptions(OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
        $encrypted = $aes->encrypt($data);

        return $encrypted;
    }

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

    public function binToArray(
        string $bytes
    ): array {
        $result = unpack('C*', $bytes);
        if (false === $result) {
            throw new RuntimeException('Can not convert bytes to array.');
        }

        return $result;
    }

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

    public function calculateKey(
        string $exponent,
        string $modulus
    ): string {
        // Remove leading 0.
        $exponent = ltrim($exponent, '0');
        $modulus = ltrim($modulus, '0');

        return sprintf('%s %s', $exponent, $modulus);
    }

    public function calculateCertificateFingerprint(
        string $certContent,
        string $algorithm = 'sha256',
        bool $rawOutput = true
    ): string {
        $normalizedCert = $this->normalizeCertificatePem($certContent);
        $fingerprint = openssl_x509_fingerprint($normalizedCert, $algorithm, $rawOutput);
        if (false === $fingerprint) {
            throw new RuntimeException('Can not calculate fingerprint for certificate.');
        }

        return $fingerprint;
    }
    /**
     * Helper to normalize PEM formatting before fingerprinting.
     * @param string $certContent
     * @return string
     */
    private function normalizeCertificatePem(string $certContent): string
    {
        $certContent = trim($certContent);
        $certContent = str_replace("\r\n", "\n", $certContent);
        $certContent = str_replace("\r", "\n", $certContent);

        $lines = explode("\n", $certContent);
        $lines = array_filter($lines, fn (string $line): bool => trim($line) !== '');
        $certContent = implode("\n", $lines) . "\n";

        return $certContent;
    }

    public function generateNonce(): string
    {
        return $this->randomService->hex(32);
    }

    public function generateTransactionKey(): string
    {
        return $this->randomService->bytes(16);
    }

    public function decomposePublicKey(Key $publicKey): array
    {
        $rsa = $this->rsaFactory->createPublic($publicKey);

        return [
            'e' => $rsa->getExponent()->toBytes(),
            'm' => $rsa->getModulus()->toBytes(),
        ];
    }

    public function generateOrderId(): string
    {
        $first = chr(rand(65, 90));
        $num = rand(0, pow(36, 3) - 1);
        $suffix = strtoupper(base_convert((string)$num, 10, 36));
        $suffix = str_pad($suffix, 3, '0', STR_PAD_LEFT);

        return $first . $suffix;
    }

    public function checkPrivateKey(Key $privateKey, string $password): bool
    {
        try {
            $this->rsaFactory->createPrivate($privateKey, $password);

            return true;
        } catch (LogicException $exception) {
            return false;
        }
    }

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
