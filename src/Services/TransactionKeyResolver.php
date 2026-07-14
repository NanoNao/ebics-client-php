<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Models\Keyring;
use RuntimeException;

/**
 * Resolves an AES transaction key from an EBICS Keyring.
 *
 * RSA-decrypts the transaction key using the user's Signature E private key.
 * Used by AESEncryptor and BufferedAESEncryptor for EBICS download decryption.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class TransactionKeyResolver
{
    /**
     * RSA-decrypt a transaction key from a Keyring.
     *
     * @param Keyring $keyring       The keyring containing user Signature E
     * @param string  $transactionKey The RSA-encrypted transaction key
     *
     * @return string The decrypted AES key
     *
     * @throws RuntimeException If Signature E or private key is missing, or RSA decryption fails
     */
    public function resolve(Keyring $keyring, string $transactionKey): string
    {
        $signatureE = $keyring->getUserSignatureE();
        if (null === $signatureE) {
            throw new RuntimeException('Signature E is not set.');
        }
        $privateKey = $signatureE->getPrivateKey();
        if (null === $privateKey) {
            throw new RuntimeException('Signature E private key is not set.');
        }

        $rsaKey = openssl_pkey_get_private($privateKey->getKey(), $keyring->getPassword());
        if (false === $rsaKey) {
            throw new RuntimeException('Cannot load private key.');
        }

        $key = '';
        $result = openssl_private_decrypt($transactionKey, $key, $rsaKey, OPENSSL_PKCS1_PADDING);

        if (false === $result || '' === $key) {
            throw new RuntimeException('Failed to RSA-decrypt transaction key: invalid key or corrupted data.');
        }

        return $key;
    }
}
