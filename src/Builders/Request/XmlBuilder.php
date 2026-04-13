<?php

namespace EbicsApi\Ebics\Builders\Request;

use DOMDocument;
use DOMElement;

/**
 * Class XmlBuilder builder for request container.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class XmlBuilder
{
    public function __construct(protected readonly DOMDocument $dom)
    {
    }

    /**
     * @param array<string, string> $attributes
     */
    public function createEmptyElement(
        string $qualifiedName,
        array $attributes = [],
        ?string $namespace = null
    ): DOMElement {
        $element = $this->dom->createElementNS(
            $namespace ?? $this->dom->documentElement->namespaceURI,
            $qualifiedName
        );

        foreach ($attributes as $attribute => $value) {
            $element->setAttribute($attribute, $value);
        }

        return $element;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function createElement(
        string $qualifiedName,
        string $value,
        array $attributes = [],
        ?string $namespace = null
    ): DOMElement {
        $element = $this->createEmptyElement($qualifiedName, $attributes, $namespace);

        $element->nodeValue = $value;

        return $element;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function appendEmptyElementTo(
        string $qualifiedName,
        DOMElement $parent,
        array $attributes = []
    ): DOMElement {
        $element = $this->createEmptyElement($qualifiedName, $attributes);

        $parent->appendChild($element);

        return $element;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function appendElementTo(
        string $qualifiedName,
        string $value,
        DOMElement $parent,
        array $attributes = []
    ): DOMElement {
        $element = $this->createElement($qualifiedName, $value, $attributes);

        $parent->appendChild($element);

        return $element;
    }
}
