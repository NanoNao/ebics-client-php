<?php

namespace EbicsApi\Ebics\Services;

use DOMDocument;
use DOMXPath;
use EbicsApi\Ebics\Contracts\OrderDataInterface;
use EbicsApi\Ebics\Exceptions\SchemaEbicsException;
use Exception;

/**
 * SchemaValidator.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal
 */
final class SchemaValidator
{
    private ?string $schemaDir;

    public function __construct(?string $schemaDir = null)
    {
        $this->schemaDir = $schemaDir;
    }

    /**
     * @param DOMDocument|OrderDataInterface $dom
     * @return void
     * @throws SchemaEbicsException
     */
    public function validate($dom): void
    {
        if ($this->schemaDir === null) {
            return;
        }

        if (!($dom instanceof DOMDocument)) {
            return;
        }

        $root = $dom->documentElement;
        if (!$root) {
            return;
        }

        $hasNamespace = false;
        foreach ($root->attributes as $attr) {
            if ($attr->nodeName === 'xsi:schemaLocation') {
                $hasNamespace = true;
                break;
            }
        }
        if (!$hasNamespace) {
            return;
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//@xsi:schemaLocation');

        if (!$nodes) {
            return;
        }

        $schemaLocation = explode(' ', $nodes[0]->nodeValue);

        $isRoot = str_contains($schemaLocation[0], 'http://www.ebics.org/');
        if ($isRoot) {
            $schema = str_replace('http://www.ebics.org/', $this->schemaDir . '/', $schemaLocation[1]);
        } else {
            $schema = $this->schemaDir . '/' . $schemaLocation[1];
        }
        if (!file_exists($schema)) {
            return;
        }

        try {
            libxml_use_internal_errors(true);
            $validate = $dom->schemaValidate($schema);
            if (!$validate) {
                $errors = libxml_get_errors();
                throw new SchemaEbicsException('Schema validation failed. ' . json_encode($errors));
            }
            libxml_use_internal_errors(false);
        } catch (Exception $exception) {
            // EbicsApi\Ebics\Builders\CustomerCreditTransfer\CustomerCreditTransferBuilder
            // have no namespaced paths, so ignore order data validations for the moment
            if (!$isRoot) {
                return;
            }

            // Add origin XML document for better inspection on error
            throw new SchemaEbicsException(
                sprintf("%s - %s", $exception->getMessage(), $dom->saveXML()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
