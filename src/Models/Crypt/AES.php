<?php

namespace EbicsApi\Ebics\Models\Crypt;

use EbicsApi\Ebics\Contracts\BufferInterface;
use EbicsApi\Ebics\Contracts\Crypt\AESInterface;
use LogicException;

/**
 * Pure-PHP implementation of AES cipher in CBC mode, backed by the OpenSSL extension.
 *
 * Supports 128-bit and 256-bit key lengths.
 * Padding scheme: ANSI X.923 (zero-padded bytes, final byte encodes the padding length).
 *
 * Requires the PHP `openssl` extension.
 */
final class AES implements AESInterface
{
    /**
     * OpenSSL engine identifier.
     */
    const ENGINE_OPENSSL = 3;

    /**
     * Active key length in bytes (16 or 32).
     * Derived automatically from the key unless set explicitly via setKeyLength().
     */
    protected int $key_length = 16;

    /**
     * Whether ANSI X.923 padding is applied on encrypt and stripped on decrypt.
     */
    protected bool $padding = true;

    /**
     * Whether the current mode supports padding.
     * Always true for CBC mode.
     */
    protected bool $paddable = false;

    /**
     * Whether the key length was set explicitly via setKeyLength().
     * When false, the key length is derived from the key bytes in setKey().
     */
    protected bool $explicit_key_length = false;

    /**
     * AES block size in bytes. Fixed at 16 for AES.
     */
    protected int $block_size = 16;

    /**
     * The active cipher engine. Currently always ENGINE_OPENSSL.
     */
    protected ?int $engine;

    /**
     * Whether the internal cipher state (IV, key, cipher name) needs to be re-initialised
     * before the next encrypt/decrypt call.
     */
    protected bool $changed = true;

    /**
     * The encryption/decryption key.
     * Defaults to a 16 null-byte key until set via setKey().
     */
    protected string $key = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

    /**
     * The Initialization Vector supplied by the caller via setIV().
     */
    protected string $iv;

    /**
     * Working copy of the IV used during encryption.
     * Initialised from $iv in clearBuffers().
     */
    protected string $encryptIV;

    /**
     * Working copy of the IV used during decryption.
     * Initialised from $iv in clearBuffers().
     */
    protected string $decryptIV;

    /**
     * OpenSSL cipher name for CBC mode, e.g. "aes-128-cbc" or "aes-256-cbc".
     *
     * @link https://www.php.net/openssl-get-cipher-methods
     */
    protected string $cipherNameOpenssl;

    /**
     * Flags passed to openssl_encrypt() / openssl_decrypt().
     * Always OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING on PHP 5.4+.
     *
     * @var int
     */
    protected $opensslOptions;

    /**
     * Initialises the cipher in CBC mode and selects the OpenSSL engine.
     */
    public function __construct()
    {
        $this->paddable = true;
        $this->setEngine();
    }

    public function setKeyLength($length)
    {
        switch (true) {
            case $length === 128:
                $this->key_length = 16;
                break;
            case $length === 224:
            case $length === 256:
                $this->key_length = 32;
                break;
            default:
                throw new LogicException('Unhandled length.');
        }

        $this->explicit_key_length = true;
        $this->changed = true;
        $this->setEngine();
    }

    public function setKey($key)
    {
        if (!$this->explicit_key_length) {
            $this->setKeyLength(strlen($key) << 3);
            $this->explicit_key_length = false;
        }

        $this->key = $key;
        $this->changed = true;
        $this->setEngine();

        if (!$this->explicit_key_length) {
            $length = strlen($key);
            switch (true) {
                case $length <= 16:
                    $this->key_length = 16;
                    break;
                case $length <= 24:
                    $this->key_length = 24;
                    break;
                default:
                    $this->key_length = 32;
            }
            $this->setEngine();
        }
    }

    public function setIV($iv)
    {
        $this->iv = $iv;
        $this->changed = true;
    }

    public function encrypt($plaintext): string
    {
        if ($this->paddable) {
            $plaintext = $this->pad($plaintext);
        }

        if ($this->changed) {
            $this->clearBuffers();
            $this->changed = false;
        }

        $result = openssl_encrypt(
            $plaintext,
            $this->cipherNameOpenssl,
            $this->key,
            $this->opensslOptions,
            $this->encryptIV
        );
        if (!$result) {
            throw new LogicException('Encryption failed.');
        }

        return $result;
    }

    /**
     * Applies ANSI X.923 padding to $text so its length is a multiple of the block size.
     *
     * Padding bytes are all 0x00 except for the final byte, which encodes
     * the total number of padding bytes added.
     *
     * When padding is disabled and the input is not block-aligned, a
     * LogicException is thrown.
     *
     * @param string $text Plaintext to pad.
     *
     * @return string Padded plaintext.
     *
     * @throws LogicException If padding is disabled and $text length is not block-aligned.
     */
    private function pad(string $text): string
    {
        $length = strlen($text);

        if (!$this->padding) {
            if ($length % $this->block_size == 0) {
                return $text;
            } else {
                throw new LogicException(
                    "The plaintext's length ($length) is not a multiple of the block size ({$this->block_size})"
                );
            }
        }

        $paddingSize = $this->block_size - (strlen($text) % $this->block_size);
        $padding = str_repeat(chr(0), $paddingSize - 1) . chr($paddingSize & 0xFF);

        return $text . $padding;
    }

    public function decryptBuffer(BufferInterface $ciphertext, BufferInterface $plaintext): void
    {
        if ($this->paddable) {
            $length = ($this->block_size - $ciphertext->length() % $this->block_size) % $this->block_size;
            $strpadRight = str_repeat(chr(0), $length);
        }

        if ($this->changed) {
            $this->clearBuffers();
            $this->changed = false;
        }

        while (!$ciphertext->eof()) {
            $chunk = $ciphertext->read();

            if ($chunk === '') {
                break;
            }

            if ($ciphertext->length() === 0 && isset($strpadRight)) {
                $chunk .= $strpadRight;
            }

            $plaintextChunk = openssl_decrypt(
                $chunk,
                $this->cipherNameOpenssl,
                $this->key,
                $this->opensslOptions,
                $this->decryptIV
            );

            if (!$plaintextChunk) {
                return;
            }

            if ($ciphertext->length() === 0 && $this->paddable) {
                $plaintextChunk = $this->unpad($plaintextChunk);
            }

            $plaintext->write($plaintextChunk);

            $this->decryptIV = substr($chunk, -16);
        }

        $this->clearBuffers();

        $plaintext->rewind();
    }

    public function decrypt($ciphertext): string
    {
        if ($this->paddable) {
            $ciphertext = str_pad(
                $ciphertext,
                strlen($ciphertext) + ($this->block_size - strlen($ciphertext) % $this->block_size) % $this->block_size,
                chr(0)
            );
        }

        if ($this->changed) {
            $this->clearBuffers();
            $this->changed = false;
        }

        if (!($plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipherNameOpenssl,
            $this->key,
            $this->opensslOptions,
            $this->decryptIV
        ))) {
            throw new LogicException('Decryption failed.');
        }

        return $this->paddable ? $this->unpad($plaintext) : $plaintext;
    }

    /**
     * Selects and validates the OpenSSL engine.
     *
     * Sets $this->engine to ENGINE_OPENSSL if the resolved cipher name
     * is available in the current OpenSSL installation, or null otherwise.
     * Marks the cipher state as changed.
     */
    private function setEngine(): void
    {
        $this->engine = null;

        $engine = self::ENGINE_OPENSSL;

        if ($this->isValidEngine($engine)) {
            $this->engine = $engine;
        }

        $this->changed = true;
    }

    public function setOpenSSLOptions($options): void
    {
        $this->opensslOptions = $options;
    }

    /**
     * Resets the working IV and trims/pads the key to the active key length.
     *
     * Called automatically before the first encrypt/decrypt after any state
     * change (setKey, setIV, setKeyLength).
     */
    private function clearBuffers(): void
    {
        $substr = substr($this->iv ?? '', 0, $this->block_size);
        $this->encryptIV = $this->decryptIV = str_pad($substr, $this->block_size, "\0");

        $this->key = str_pad(substr($this->key, 0, $this->key_length), $this->key_length, "\0");
    }

    /**
     * Validates the given engine and, if valid, initialises the cipher name and OpenSSL options.
     *
     * For ENGINE_OPENSSL this checks that the resolved cipher (e.g. "aes-128-cbc")
     * is listed by openssl_get_cipher_methods().
     *
     * @param int $engine One of the ENGINE_* constants.
     *
     * @return bool True if the engine is available and ready to use.
     *
     * @throws LogicException For unknown engine values.
     */
    private function isValidEngine(int $engine): bool
    {
        if (empty($engine)) {
            return false;
        }

        switch ($engine) {
            case self::ENGINE_OPENSSL:
                if ($this->block_size != 16) {
                    return false;
                }
                $this->cipherNameOpenssl = 'aes-' . ($this->key_length << 3) . '-cbc';
                break;
            default:
                throw new LogicException('Unhandled engine.');
        }

        $this->opensslOptions = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;

        return in_array($this->cipherNameOpenssl, openssl_get_cipher_methods());
    }

    /**
     * Strips ANSI X.923 padding from a decrypted block.
     *
     * Reads the padding length from the final byte and removes that many bytes.
     * When padding is disabled the text is returned unchanged.
     *
     * @param string $text Decrypted, padded text.
     *
     * @return string Unpadded plaintext.
     *
     * @throws LogicException If the padding length byte is zero or exceeds the block size.
     */
    private function unpad(string $text): string
    {
        if (!$this->padding) {
            return $text;
        }

        $length = ord($text[strlen($text) - 1]);

        if (!$length || $length > $this->block_size) {
            throw new LogicException('Length incorrect.');
        }

        return substr($text, 0, -$length);
    }
}
