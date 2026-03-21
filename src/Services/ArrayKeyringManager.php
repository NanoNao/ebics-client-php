<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Models\Keyring;
use LogicException;

/**
 * EBICS Keyring representation manage one keyring stored in the array.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class ArrayKeyringManager extends KeyringManager
{
    /**
     * @param array<mixed>|string $resource
     */
    public function loadKeyring($resource, string $passphrase, string $defaultVersion = Keyring::VERSION_25): Keyring
    {
        if (!is_array($resource)) {
            throw new LogicException('Expects array.');
        }
        if (!empty($resource)) {
            $keyring = $this->keyringFactory->createKeyringFromData($resource);
        } else {
            $keyring = $this->createKeyring($defaultVersion);
        }
        $keyring->setPassword($passphrase);

        return $keyring;
    }

    /**
     * @param array<mixed>|string $resource
     * @param-out array<mixed> $resource
     */
    public function saveKeyring(Keyring $keyring, &$resource): void
    {
        if (!is_array($resource)) {
            throw new LogicException('Expects array.');
        }
        $resource = $this->keyringFactory->buildDataFromKeyring($keyring);
    }
}
