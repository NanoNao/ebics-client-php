<?php

namespace EbicsApi\Ebics\Tests\Models\Crypt;

use EbicsApi\Ebics\Models\Crypt\ASN1;
use EbicsApi\Ebics\Models\Crypt\BigInteger;
use PHPUnit\Framework\TestCase;

/**
 * Class ASN1Test.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
class ASN1Test extends TestCase
{
    /**
     * @var ASN1
     */
    private $asn1;

    protected function setUp(): void
    {
        $this->asn1 = new ASN1();
    }

    public function testDecodeBERInteger(): void
    {
        $encoded = "\x02\x01\x05";
        $decoded = $this->asn1->decodeBER($encoded);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals(ASN1::TYPE_INTEGER, $decoded[0]['type']);
        $this->assertInstanceOf(BigInteger::class, $decoded[0]['content']);
        $this->assertEquals('5', $decoded[0]['content']->toString());
    }

    public function testDecodeBERBoolean(): void
    {
        $encoded = "\x01\x01\xFF";
        $decoded = $this->asn1->decodeBER($encoded);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals(ASN1::TYPE_BOOLEAN, $decoded[0]['type']);
        $this->assertTrue($decoded[0]['content']);

        $encoded = "\x01\x01\x00";
        $decoded = $this->asn1->decodeBER($encoded);
        $this->assertFalse($decoded[0]['content']);
    }

    public function testDecodeBEROctetString(): void
    {
        $encoded = "\x04\x02\x68\x69";
        $decoded = $this->asn1->decodeBER($encoded);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals(ASN1::TYPE_OCTET_STRING, $decoded[0]['type']);
        $this->assertEquals('hi', $decoded[0]['content']);
    }

    public function testDecodeBERSequence(): void
    {
        $encoded = "\x30\x06\x02\x01\x05\x01\x01\xFF";
        $decoded = $this->asn1->decodeBER($encoded);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals(ASN1::TYPE_SEQUENCE, $decoded[0]['type']);
        $this->assertIsArray($decoded[0]['content']);
        $this->assertCount(2, $decoded[0]['content']);

        $item1 = $decoded[0]['content'][0];
        $this->assertEquals(ASN1::TYPE_INTEGER, $item1['type']);
        $this->assertEquals('5', $item1['content']->toString());

        $item2 = $decoded[0]['content'][1];
        $this->assertEquals(ASN1::TYPE_BOOLEAN, $item2['type']);
        $this->assertTrue($item2['content']);
    }

    public function testEncodeDERInteger(): void
    {
        $source = new BigInteger(5);
        $mapping = ['type' => ASN1::TYPE_INTEGER];

        $encoded = $this->asn1->encodeDER($source, $mapping);
        $this->assertEquals("\x02\x01\x05", $encoded);

        $encodedRaw = $this->asn1->encodeDER(5, $mapping);
        $this->assertEquals("\x02\x01\x05", $encodedRaw);
    }

    public function testEncodeDERBoolean(): void
    {
        $mapping = ['type' => ASN1::TYPE_BOOLEAN];

        $encoded = $this->asn1->encodeDER(true, $mapping);
        $this->assertEquals("\x01\x01\xFF", $encoded);

        $encodedFalse = $this->asn1->encodeDER(false, $mapping);
        $this->assertEquals("\x01\x01\x00", $encodedFalse);
    }

    public function testEncodeDEROctetString(): void
    {
        $source = base64_encode('hi');

        $mapping = ['type' => ASN1::TYPE_OCTET_STRING];
        $encoded = $this->asn1->encodeDER($source, $mapping);

        $this->assertEquals("\x04\x02\x68\x69", $encoded);
    }

    public function testEncodeDERSequence(): void
    {
        $source = [
            'intVal' => new BigInteger(5),
            'boolVal' => true
        ];

        $mapping = [
            'type' => ASN1::TYPE_SEQUENCE,
            'children' => [
                'intVal' => ['type' => ASN1::TYPE_INTEGER],
                'boolVal' => ['type' => ASN1::TYPE_BOOLEAN]
            ]
        ];

        $encoded = $this->asn1->encodeDER($source, $mapping);
        $this->assertEquals("\x30\x06\x02\x01\x05\x01\x01\xFF", $encoded);
    }

    public function testAsn1Map(): void
    {
        $encoded = "\x30\x06\x02\x01\x05\x01\x01\xFF";
        $decoded = $this->asn1->decodeBER($encoded);

        $mapping = [
            'type' => ASN1::TYPE_SEQUENCE,
            'children' => [
                'myInt' => ['type' => ASN1::TYPE_INTEGER],
                'myBool' => ['type' => ASN1::TYPE_BOOLEAN]
            ]
        ];

        $mapped = $this->asn1->asn1map($decoded[0], $mapping);

        $this->assertIsArray($mapped);
        $this->assertArrayHasKey('myInt', $mapped);
        $this->assertArrayHasKey('myBool', $mapped);
        $this->assertInstanceOf(BigInteger::class, $mapped['myInt']);
        $this->assertEquals('5', $mapped['myInt']->toString());
        $this->assertTrue($mapped['myBool']);
    }

    public function testLoadOIDsAndDecode(): void
    {
        $encoded = "\x06\x03\x2A\x86\x48";

        $decoded = $this->asn1->decodeBER($encoded);
        $this->assertEquals('1.2.840', $decoded[0]['content']);

        $this->asn1->loadOIDs(['1.2.840' => 'US']);

        $mapping = ['type' => ASN1::TYPE_OBJECT_IDENTIFIER];
        $mapped = $this->asn1->asn1map($decoded[0], $mapping);

        $this->assertEquals('US', $mapped);
    }
}
