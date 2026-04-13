<?php

namespace EbicsApi\Ebics\Handlers;

use EbicsApi\Ebics\Contracts\UserSignatureHandlerInterface;
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

    public function __construct(
        protected readonly User $user,
        protected readonly Keyring $keyring,
        protected readonly CryptService $cryptService,
        protected readonly SchemaValidator $validator
    ) {
    }

    public function handle(UserSignature $xml, string $digest): void
    {
        $this->handleXml($xml, $digest);

        $this->validator->validate($xml);
    }

    abstract protected function handleXml(UserSignature $xml, string $digest): void;
}
