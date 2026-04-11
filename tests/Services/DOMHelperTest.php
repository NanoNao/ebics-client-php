<?php

namespace EbicsApi\Ebics\Tests\Services;

use DOMDocument;
use DOMXPath;
use EbicsApi\Ebics\Services\DOMHelper;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for DOMHelper.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class DOMHelperTest extends TestCase
{
    /**
     * Test safeItems returns DOMNodeList when valid.
     */
    public function testSafeItemsReturnsDOMNodeListWhenValid(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<root><item>1</item><item>2</item></root>');

        $xpath = new DOMXPath($dom);
        $nodeList = $xpath->query('//item');

        $result = DOMHelper::safeItems($nodeList);

        self::assertEquals(2, $result->length);
    }

    /**
     * Test safeItems throws exception when false is passed.
     */
    public function testSafeItemsThrowsExceptionWhenFalse(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DOM Node List should not be empty.');

        DOMHelper::safeItems(false);
    }

    /**
     * Test safeItem returns first node.
     */
    public function testSafeItemReturnsFirstNode(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<root><item>first</item><item>second</item></root>');

        $xpath = new DOMXPath($dom);
        $nodeList = $xpath->query('//item');

        $result = DOMHelper::safeItem($nodeList);

        self::assertEquals('first', $result->nodeValue);
    }

    /**
     * Test safeItem throws exception when list is empty.
     */
    public function testSafeItemThrowsExceptionWhenListEmpty(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<root></root>');

        $xpath = new DOMXPath($dom);
        $nodeList = $xpath->query('//item');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DOM Node List should have an item.');

        DOMHelper::safeItem($nodeList);
    }

    /**
     * Test safeItem throws exception when false is passed.
     */
    public function testSafeItemThrowsExceptionWhenFalse(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DOM Node List should not be empty.');

        DOMHelper::safeItem(false);
    }

    /**
     * Test safeItemValue returns node value.
     */
    public function testSafeItemValueReturnsNodeValue(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<root><value>test_value</value></root>');

        $xpath = new DOMXPath($dom);
        $nodeList = $xpath->query('//value');

        $result = DOMHelper::safeItemValue($nodeList);

        self::assertEquals('test_value', $result);
    }

    /**
     * Test safeItemValue throws exception when list is empty.
     */
    public function testSafeItemValueThrowsExceptionWhenEmpty(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<root></root>');

        $xpath = new DOMXPath($dom);
        $nodeList = $xpath->query('//value');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DOM Node List should have an item.');

        DOMHelper::safeItemValue($nodeList);
    }

    /**
     * Test safeItemValueOrNull returns value when node exists.
     */
    public function testSafeItemValueOrNullReturnsValueWhenNodeExists(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<root><value>test_value</value></root>');

        $xpath = new DOMXPath($dom);
        $nodeList = $xpath->query('//value');

        $result = DOMHelper::safeItemValueOrNull($nodeList);

        self::assertEquals('test_value', $result);
    }

    /**
     * Test safeItemValueOrNull returns null when node doesn't exist.
     */
    public function testSafeItemValueOrNullReturnsNullWhenNodeMissing(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<root></root>');

        $xpath = new DOMXPath($dom);
        $nodeList = $xpath->query('//value');

        $result = DOMHelper::safeItemValueOrNull($nodeList);

        self::assertNull($result);
    }

    /**
     * Test safeItemValueOrNull throws exception when query fails.
     */
    public function testSafeItemValueOrNullThrowsExceptionWhenQueryFails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DOM Node List should not be empty.');

        DOMHelper::safeItemValueOrNull(false);
    }

    /**
     * Test safeItems with empty DOMNodeList.
     */
    public function testSafeItemsWorksWithEmptyList(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<root></root>');

        $xpath = new DOMXPath($dom);
        $nodeList = $xpath->query('//nonexistent');

        $result = DOMHelper::safeItems($nodeList);

        self::assertEquals(0, $result->length);
    }

    /**
     * Test safeItemValue with namespace.
     */
    public function testSafeItemValueWithNamespace(): void
    {
        $xml = '<?xml version="1.0"?>
        <root xmlns:ns="http://example.com">
            <ns:value>namespaced_value</ns:value>
        </root>';

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', 'http://example.com');
        $nodeList = $xpath->query('//ns:value');

        $result = DOMHelper::safeItemValue($nodeList);

        self::assertEquals('namespaced_value', $result);
    }
}
