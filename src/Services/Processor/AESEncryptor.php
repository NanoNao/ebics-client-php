<?php

namespace EbicsApi\Ebics\Services\Processor;

use EbicsApi\Ebics\Contracts\Processor\AESEncryptorInterface;
use EbicsApi\Ebics\Models\Crypt\AES;
use EbicsApi\Ebics\Services\TransactionKeyResolver;
use RuntimeException;

/**
 * AES encrypt/decrypt pipe.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class AESEncryptor implements AESEncryptorInterface
{
    private const MAX_DATA_LENGTH = 10485760;

    private AES $aes;

    public function __construct(private readonly TransactionKeyResolver $keyResolver)
    {
        $this->aes = new AES();
    }

    /**
     * @param array<string, mixed> $context Context keys:
     *   transactionKey (string — raw AES key)
     *   key (string — raw AES key, alternative to transactionKey)
     *   iv (string — optional custom IV, defaults to zero block)
     *   cipher (string — optional cipher method, defaults to aes-128-cbc)
     */
    public function encrypt(string $data, array $context = []): string
    {
        $length = strlen($data);

        if ($length + $this->aes->getBlockSize() > self::MAX_DATA_LENGTH) {
            throw new RuntimeException(sprintf(
                'Data exceeds maximum length of %d bytes, got %d bytes. Use buffered analog.',
                self::MAX_DATA_LENGTH,
                $length
            ));
        }

        $key = $context['transactionKey'] ?? $context['key'];
        $cipher = $context['cipher'] ?? 'aes-128-cbc';
        $iv = $context['iv'] ?? str_repeat("\0", $this->aes->getBlockSize());

        return $this->aes->encryptBlock($this->aes->pad($data), $key, $cipher, $iv);
    }

    /**
     * Decrypt data with AES-CBC.
     *
     * Accepts either:
     *  - context['key']           → raw AES key (general use)
     *  - context['keyring']       → Keyring + context['transactionKey'] → RSA-encrypted (EBICS use)
     *
     * Optional context keys:
     *  - context['iv']            → custom IV (defaults to zero block)
     *  - context['cipher']        → cipher method (defaults to aes-128-cbc)
     *
     * @param array<string, mixed> $context
     */
    public function decrypt(string $data, array $context = []): string
    {
        $length = strlen($data);

        if ($length > self::MAX_DATA_LENGTH) {
            throw new RuntimeException(sprintf(
                'Data exceeds maximum length of %d bytes, got %d bytes. Use buffered analog.',
                self::MAX_DATA_LENGTH,
                $length
            ));
        }

        $key = $this->resolveKey($context);
        $cipher = $context['cipher'] ?? 'aes-128-cbc';
        $iv = $context['iv'] ?? str_repeat("\0", $this->aes->getBlockSize());

        return $this->aes->unpad($this->aes->decryptBlock($data, $key, $cipher, $iv));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveKey(array $context): string
    {
        if (isset($context['key'])) {
            /** @var string */
            return $context['key'];
        }

        return $this->keyResolver->resolve($context['keyring'], $context['transactionKey']);
    }
}
