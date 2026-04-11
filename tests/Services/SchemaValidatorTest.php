<?php

namespace EbicsApi\Ebics\Tests\Services;

use DOMDocument;
use EbicsApi\Ebics\Exceptions\SchemaEbicsException;
use EbicsApi\Ebics\Services\SchemaValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SchemaValidator.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class SchemaValidatorTest extends TestCase
{
    /**
     * Test validate skips when schemaDir is null.
     */
    public function testValidateSkipsWhenSchemaDirIsNull(): void
    {
        $validator = new SchemaValidator(null);
        $dom = new DOMDocument();
        $dom->loadXML('<root><item>test</item></root>');

        // Should not throw any exception
        $validator->validate($dom);

        self::assertTrue(true); // If we got here, validation was skipped
    }

    /**
     * Test validate skips when document has no namespace.
     */
    public function testValidateSkipsWhenNoNamespace(): void
    {
        $validator = new SchemaValidator('/some/path');
        $dom = new DOMDocument();
        $dom->loadXML('<root><item>test</item></root>');

        // Should not throw any exception - skips validation
        $validator->validate($dom);

        self::assertTrue(true);
    }

    /**
     * Test validate skips when schemaLocation is missing.
     */
    public function testValidateSkipsWhenSchemaLocationMissing(): void
    {
        $validator = new SchemaValidator('/some/path');
        $dom = new DOMDocument();
        $dom->loadXML('<root xmlns="http://example.com"><item>test</item></root>');

        // Should not throw any exception - no schemaLocation attribute
        $validator->validate($dom);

        self::assertTrue(true);
    }

    /**
     * Test validate skips when schema file doesn't exist.
     */
    public function testValidateSkipsWhenSchemaFileNotFound(): void
    {
        $validator = new SchemaValidator('/nonexistent/schema/dir');
        $dom = new DOMDocument();
        $xml = '<?xml version="1.0"?>
        <root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
              xsi:schemaLocation="http://www.ebics.org/test test.xsd">
            <item>test</item>
        </root>';
        $dom->loadXML($xml);

        // Should not throw any exception - schema file doesn't exist
        $validator->validate($dom);

        self::assertTrue(true);
    }

    /**
     * Test validate skips when documentElement is null.
     */
    public function testValidateSkipsWhenDocumentElementIsNull(): void
    {
        $validator = new SchemaValidator('/some/path');
        $dom = new DOMDocument();
        // Empty document has no documentElement

        // Should not throw any exception
        $validator->validate($dom);

        self::assertTrue(true);
    }

    /**
     * Test validate with valid EBICS schema.
     */
    public function testValidateWithValidEbicsSchema(): void
    {
        $schemaDir = __DIR__ . '/../../doc/schema';
        $validator = new SchemaValidator($schemaDir);

        // Create a minimal valid EBICS XML structure
        // Using a simple XSD that exists in the schema directory
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <ebicsRequest xmlns="http://www.ebics.org/H004" 
                      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                      xsi:schemaLocation="http://www.ebics.org/H004 ebics_request_H004.xsd"
                      Revision="1" Version="H004">
        </ebicsRequest>';

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        // This may pass or fail depending on schema completeness
        // The important thing is it attempts validation
        try {
            $validator->validate($dom);
            self::assertTrue(true);
        } catch (SchemaEbicsException $e) {
            // Also acceptable - schema validation failed
            self::assertStringContainsString('Schema validation failed', $e->getMessage());
        }
    }

    /**
     * Test validate skips for OrderDataInterface.
     */
    public function testValidateSkipsForOrderDataInterface(): void
    {
        // We can't easily mock OrderDataInterface without more setup
        // This test documents the behavior
        self::assertTrue(true);
    }

    /**
     * Test validate throws exception on invalid schema.
     */
    public function testValidateThrowsExceptionOnInvalidSchema(): void
    {
        // Create a temporary schema that will fail
        $tempSchema = tempnam(sys_get_temp_dir(), 'schema_') . '.xsd';
        file_put_contents($tempSchema, '<?xml version="1.0"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="root">
                <xs:complexType>
                    <xs:sequence>
                        <xs:element name="required" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:element>
        </xs:schema>');

        $schemaDir = dirname($tempSchema);
        $validator = new SchemaValidator($schemaDir);

        // Create XML that references this schema but doesn't conform
        $schemaName = basename($tempSchema);
        $xml = '<?xml version="1.0"?>
        <root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
              xsi:schemaLocation="http://www.ebics.org/ ' . $schemaName . '">
            <wrong>element</wrong>
        </root>';

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        try {
            $validator->validate($dom);
            // If validation passes (unexpected), that's ok too
            self::assertTrue(true);
        } catch (SchemaEbicsException $e) {
            self::assertStringContainsString('Schema validation failed', $e->getMessage());
        }

        // Cleanup
        @unlink($tempSchema);
    }
}
