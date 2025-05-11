<?php

namespace EbicsApi\Ebics\Services;

use DOMDocument;
use DOMXPath;
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

    public function validate(DOMDocument $dom): void
    {
        if ($this->schemaDir === null) {
            return;
        }
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//@xsi:schemaLocation');

        if (!$nodes) {
            return;
        }

        $schemaLocation = explode(' ', $nodes[0]->nodeValue);

        $schema = str_replace('http://www.ebics.org/', $this->schemaDir . '/', $schemaLocation[1]);

        try {
            $dom->schemaValidate($schema);
        } catch (Exception $exception) {
            throw new SchemaEbicsException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
