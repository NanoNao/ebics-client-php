<?php

namespace EbicsApi\Ebics\Builders\Request;

use Closure;
use DOMDocument;
use DOMElement;
use EbicsApi\Ebics\Services\Base64Service;
use EbicsApi\Ebics\Services\CryptService;

/**
 * Class HeaderBuilder builder for request container.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class HeaderBuilder extends XmlBuilder
{
    protected readonly CryptService $cryptService;
    protected readonly Base64Service $base64Service;
    protected DOMElement $instance;

    public function __construct(CryptService $cryptService, Base64Service $base64Service, DOMDocument $dom)
    {
        $this->cryptService = $cryptService;
        $this->base64Service = $base64Service;
        parent::__construct($dom);
    }

    public function createInstance(): HeaderBuilder
    {
        $this->instance = $this->createEmptyElement('header', ['authenticate' => 'true']);

        return $this;
    }

    abstract public function addStatic(Closure $callback): HeaderBuilder;

    public function addMutable(?Closure $callable = null): HeaderBuilder
    {
        $mutableBuilder = new MutableBuilder($this->dom);
        $this->instance->appendChild($mutableBuilder->createInstance()->getInstance());

        if (null !== $callable) {
            call_user_func($callable, $mutableBuilder);
        }

        return $this;
    }

    public function getInstance(): DOMElement
    {
        return $this->instance;
    }
}
