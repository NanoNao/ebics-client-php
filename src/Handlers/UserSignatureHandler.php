<?php

namespace EbicsApi\Ebics\Handlers;

use EbicsApi\Ebics\Contracts\UserSignatureHandlerInterface;
use EbicsApi\Ebics\Exceptions\EbicsException;
use EbicsApi\Ebics\Handlers\Traits\C14NTrait;
use EbicsApi\Ebics\Handlers\Traits\XPathTrait;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Models\User;
use EbicsApi\Ebics\Models\UserSignature;
use EbicsApi\Ebics\Services\CryptService;
use EbicsApi\Ebics\Services\SchemaValidator;

/**
 * Class AuthSignatureHandler manage body DOM elements.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal
 */
abstract class UserSignatureHandler implements UserSignatureHandlerInterface
{
    use C14NTrait;
    use XPathTrait;

    protected User $user;
    protected Keyring $keyring;
    protected CryptService $cryptService;
    protected SchemaValidator $validator;

    public function __construct(User $user, Keyring $keyring, CryptService $cryptService, SchemaValidator $validator)
    {
        $this->user = $user;
        $this->keyring = $keyring;
        $this->cryptService = $cryptService;
        $this->validator = $validator;
    }

    public function handle(UserSignature $xml, string $digest): void
    {
        $this->handleXml($xml, $digest);

        $this->validator->validate($xml);
    }

    abstract protected function handleXml(UserSignature $xml, string $digest): void;
}
