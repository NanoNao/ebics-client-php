<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Services\XmlService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for XmlService.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class XmlServiceTest extends TestCase
{
    private XmlService $xmlService;

    protected function setUp(): void
    {
        $this->xmlService = new XmlService();
    }

    /**
     * Test extractFilesFromString with single XML document.
     */
    public function testExtractFilesFromSingleXmlDocument(): void
    {
        $xmlContent = '<?xml version="1.0"?><root><item>test</item></root>';

        $files = $this->xmlService->extractFilesFromString($xmlContent);

        self::assertCount(1, $files);
        self::assertEquals($xmlContent, $files[0]);
    }

    /**
     * Test extractFilesFromString with two XML documents.
     */
    public function testExtractFilesFromTwoXmlDocuments(): void
    {
        $xmlContent = '<?xml version="1.0"?><root1><item>test1</item></root1>';
        $xmlContent .= '<?xml version="1.0"?><root2><item>test2</item></root2>';

        $files = $this->xmlService->extractFilesFromString($xmlContent);

        self::assertCount(2, $files);
        self::assertStringStartsWith('<?xml', $files[0]);
        self::assertStringStartsWith('<?xml', $files[1]);
        self::assertStringContainsString('root1', $files[0]);
        self::assertStringContainsString('root2', $files[1]);
    }

    /**
     * Test extractFilesFromString with three XML documents.
     */
    public function testExtractFilesFromThreeXmlDocuments(): void
    {
        $xmlContent = '<?xml version="1.0"?><doc1/><?xml version="1.0"?><doc2/><?xml version="1.0"?><doc3/>';

        $files = $this->xmlService->extractFilesFromString($xmlContent);

        self::assertCount(3, $files);
        self::assertStringContainsString('doc1', $files[0]);
        self::assertStringContainsString('doc2', $files[1]);
        self::assertStringContainsString('doc3', $files[2]);
    }

    /**
     * Test extractFilesFromString with empty string.
     */
    public function testExtractFilesFromEmptyString(): void
    {
        $xmlContent = '<?xml version="1.0"?><root/>';

        $files = $this->xmlService->extractFilesFromString($xmlContent);

        self::assertCount(1, $files);
    }

    /**
     * Test extractFilesFromString with XML containing whitespace.
     */
    public function testExtractFilesFromXmlWithWhitespace(): void
    {
        $xmlContent = '<?xml version="1.0"?><root>test</root>  <?xml version="1.0"?><root2>test2</root2>';

        $files = $this->xmlService->extractFilesFromString($xmlContent);

        self::assertCount(2, $files);
        self::assertStringStartsWith('<?xml', $files[0]);
    }

    /**
     * Test extractFilesFromString trims extracted XML.
     */
    public function testExtractFilesFromStringTrimsContent(): void
    {
        $xmlContent = '<?xml version="1.0"?><root>test</root><?xml version="1.0"?><root2>test2</root2>';

        $files = $this->xmlService->extractFilesFromString($xmlContent);

        self::assertCount(2, $files);
        // Should be trimmed
        self::assertStringEndsWith('</root>', $files[0]);
        self::assertStringEndsWith('</root2>', $files[1]);
    }

    /**
     * Test extractFilesFromString with XML declaration attributes.
     */
    public function testExtractFilesFromStringWithDeclarationAttributes(): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?><root1/>';
        $xmlContent .= '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><root2/>';

        $files = $this->xmlService->extractFilesFromString($xmlContent);

        self::assertCount(2, $files);
        self::assertStringContainsString('root1', $files[0]);
        self::assertStringContainsString('root2', $files[1]);
    }
}
