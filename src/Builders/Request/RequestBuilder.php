<?php

namespace EbicsApi\Ebics\Builders\Request;

use Closure;
use EbicsApi\Ebics\Handlers\AuthSignatureHandler;
use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Services\SchemaValidator;

/**
 * Class RequestBuilder builder for model @see \EbicsApi\Ebics\Models\Http\Request
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class RequestBuilder
{
    private ?Request $instance;

    private RootBuilder $rootBuilder;

    public function __construct(
        private readonly AuthSignatureHandler $authSignatureHandler,
        private readonly SchemaValidator $validator
    ) {
    }

    public function createInstance(Closure $callback): RequestBuilder
    {
        $this->instance = new Request();

        $this->rootBuilder = $callback($this->instance);

        return $this;
    }

    public function addContainerUnsecured(Closure $callback): RequestBuilder
    {
        if ($this->instance === null) {
            throw new \RuntimeException('Request instance not created.');
        }
        $this->instance->appendChild($this->rootBuilder->createUnsecured()->getInstance());

        $callback($this->rootBuilder);

        return $this;
    }

    public function addContainerSecuredNoPubKeyDigests(Closure $callback): RequestBuilder
    {
        if ($this->instance === null) {
            throw new \RuntimeException('Request instance not created.');
        }
        $this->instance->appendChild($this->rootBuilder->createSecuredNoPubKeyDigests()->getInstance());

        $callback($this->rootBuilder);

        return $this;
    }

    public function addContainerSecured(Closure $callback): RequestBuilder
    {
        if ($this->instance === null) {
            throw new \RuntimeException('Request instance not created.');
        }
        $this->instance->appendChild($this->rootBuilder->createSecured()->getInstance());

        $callback($this->rootBuilder);

        return $this;
    }

    public function addContainerUnsigned(Closure $callback): RequestBuilder
    {
        if ($this->instance === null) {
            throw new \RuntimeException('Request instance not created.');
        }
        $this->instance->appendChild($this->rootBuilder->createUnsigned()->getInstance());

        $callback($this->rootBuilder);

        return $this;
    }

    public function addContainerHEV(Closure $callback): RequestBuilder
    {
        if ($this->instance === null) {
            throw new \RuntimeException('Request instance not created.');
        }
        $this->instance->appendChild($this->rootBuilder->createHEV()->getInstance());

        $callback($this->rootBuilder);

        return $this;
    }

    public function popInstance(): Request
    {
        if ($this->instance === null) {
            throw new \RuntimeException('Request instance not created.');
        }
        if ($this->rootBuilder->isSecured($this->instance->documentElement->tagName)) {
            $this->authSignatureHandler->handle($this->instance);
        }

        $this->validator->validate($this->instance);

        $instance = $this->instance;
        $this->instance = null;

        return $instance;
    }
}
