<?php

namespace EbicsApi\Ebics\Services;

use DOMDocument;
use DOMXPath;
use EbicsApi\Ebics\Contracts\OrderDataInterface;
use EbicsApi\Ebics\Contracts\SchemaValidatorInterface;
use EbicsApi\Ebics\Exceptions\SchemaEbicsException;
use Exception;

/**
 * XML Schema (XSD) validation service for EBICS protocol messages.
 *
 * Validates DOMDocument instances against EBICS XSD schema files to ensure
 * XML messages conform to the EBICS standard before sending to banks or after
 * receiving responses. This helps catch protocol compliance errors early.
 *
 * The validator reads the `xsi:schemaLocation` attribute from the XML document
 * to determine which schema file to validate against, then maps it to the
 * local schema directory.
 *
 * Schema files are expected to be in the directory specified by $schemaDir
 * (default: `doc/schema/`). The validator automatically transforms the
 * namespace URL from `http://www.ebics.org/` to the local path.
 *
 * Note: Validation is silently skipped if:
 * - No schema directory is configured
 * - The document is an OrderDataInterface (non-namespaced order data)
 * - The XML has no namespace or no schemaLocation attribute
 * - The schema file doesn't exist locally
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal This class is for internal use and may change without notice.
 */
final class SchemaValidator implements SchemaValidatorInterface
{
    private ?string $schemaDir;

    /**
     * Constructor.
     *
     * @param string|null $schemaDir Path to the directory containing EBICS XSD schema files.
     *                               If null, validation is skipped. Expected path: `doc/schema/`.
     */
    public function __construct(?string $schemaDir = null)
    {
        $this->schemaDir = $schemaDir;
    }

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
