<?php

namespace EbicsApi\Ebics\Contexts;

/**
 * Business transactions & formats.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Geoffroy de Corbiac
 */
abstract class BTFContext extends ServiceContext
{
    private bool $signatureFlag = false;
    private bool $signatureFlagEds = false;

    public function setSignatureFlag(bool $signatureFlag): self
    {
        $this->signatureFlag = $signatureFlag;

        return $this;
    }

    public function getSignatureFlag(): bool
    {
        return $this->signatureFlag;
    }

    public function setSignatureFlagEds(bool $signatureFlagEds): self
    {
        $this->signatureFlagEds = $signatureFlagEds;

        return $this;
    }

    public function getSignatureFlagEds(): bool
    {
        return $this->signatureFlagEds;
    }
}
