<?php

namespace EbicsApi\Ebics\Contracts;

use DOMDocument;
use EbicsApi\Ebics\Exceptions\SchemaEbicsException;

/**
 * XML Schema Validator interface.
 *
 * Validates DOMDocument instances against EBICS XSD (XML Schema Definition)
 * files to ensure XML messages conform to the EBICS protocol standard.
 *
 * EBICS Protocol Context:
 * All EBICS requests and responses must conform to specific XML schemas
 * defined by the EBICS specification. Schema validation helps catch
 * protocol compliance errors early, before sending requests to the bank
 * or when processing bank responses.
 *
 * The validator automatically determines the appropriate XSD schema file
 * by reading the `xsi:schemaLocation` attribute from the XML document root,
 * then mapping the schema URL to a local file path in the `doc/schema/`
 * directory.
 *
 * Supported schemas include:
 * - H001/H002/H003/H004/H005: EBICS 2.4/2.5 request/response schemas
 * - H006: EBICS 3.0 request/response schema
 * - H000: System-level response schema (HEV order)
 * - Various pain.001, camt.053, etc.: Payment and account statement formats
 *
 * Validation is silently skipped if:
 * - No schema directory is configured
 * - The document has no XML namespace
 * - The schema file doesn't exist locally
 * - The document is an OrderDataInterface (non-namespaced order data)
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface SchemaValidatorInterface
{
    /**
     * Validate a DOMDocument against the EBICS XSD schema.
     *
     * The validation process:
     * 1. Checks if the schema directory is configured (skips if null)
     * 2. Extracts the `xsi:schemaLocation` attribute from the document root
     * 3. Maps the schema URL to a local file path
     * 4. Performs schema validation using `DOMDocument::schemaValidate()`
     *
     * @param DOMDocument|OrderDataInterface $dom The XML document to validate.
     *                                           OrderDataInterface instances are skipped.
     *
     * @return void
     *
     * @throws SchemaEbicsException If schema validation fails. The exception
     *                              message includes the original XML for debugging.
     */
    public function validate($dom): void;
}
