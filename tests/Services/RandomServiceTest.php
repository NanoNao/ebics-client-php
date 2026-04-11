<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Services\RandomService;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RandomService.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class RandomServiceTest extends TestCase
{
    private RandomService $randomService;

    protected function setUp(): void
    {
        $this->randomService = new RandomService();
    }

    /**
     * Test hex generates correct length.
     */
    public function testHexGeneratesCorrectLength(): void
    {
        $hex = $this->randomService->hex(16);

        self::assertEquals(16, strlen($hex));
    }

    /**
     * Test hex generates only valid hex characters.
     */
    public function testHexGeneratesValidCharacters(): void
    {
        $hex = $this->randomService->hex(32);

        self::assertMatchesRegularExpression('/^[0-9A-F]{32}$/', $hex);
    }

    /**
     * Test hex never starts with 0.
     */
    public function testHexNeverStartsWithZero(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $hex = $this->randomService->hex(8);
            self::assertNotEquals('0', $hex[0], "Hex string should not start with 0");
        }
    }

    /**
     * Test hex generates different values.
     */
    public function testHexGeneratesDifferentValues(): void
    {
        $hex1 = $this->randomService->hex(16);
        $hex2 = $this->randomService->hex(16);

        self::assertNotEquals($hex1, $hex2);
    }

    /**
     * Test digits generates correct length.
     */
    public function testDigitsGeneratesCorrectLength(): void
    {
        $digits = $this->randomService->digits(10);

        self::assertEquals(10, strlen($digits));
    }

    /**
     * Test digits generates only numeric characters.
     */
    public function testDigitsGeneratesValidCharacters(): void
    {
        $digits = $this->randomService->digits(20);

        self::assertMatchesRegularExpression('/^[0-9]{20}$/', $digits);
    }

    /**
     * Test digits never starts with 0.
     */
    public function testDigitsNeverStartsWithZero(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $digits = $this->randomService->digits(8);
            self::assertNotEquals('0', $digits[0], "Digit string should not start with 0");
        }
    }

    /**
     * Test digits generates different values.
     */
    public function testDigitsGeneratesDifferentValues(): void
    {
        $digits1 = $this->randomService->digits(10);
        $digits2 = $this->randomService->digits(10);

        self::assertNotEquals($digits1, $digits2);
    }

    /**
     * Test bytes generates correct length.
     */
    public function testBytesGeneratesCorrectLength(): void
    {
        $bytes = $this->randomService->bytes(16);

        self::assertEquals(16, strlen($bytes));
    }

    /**
     * Test bytes generates random binary data.
     */
    public function testBytesGeneratesBinaryData(): void
    {
        $bytes = $this->randomService->bytes(32);

        // Binary data may contain non-printable characters
        self::assertIsString($bytes);
    }

    /**
     * Test bytes throws exception for length less than 1.
     */
    public function testBytesThrowsExceptionForInvalidLength(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Minimal length is 1');

        $this->randomService->bytes(0);
    }

    /**
     * Test bytes generates different values.
     */
    public function testBytesGeneratesDifferentValues(): void
    {
        $bytes1 = $this->randomService->bytes(16);
        $bytes2 = $this->randomService->bytes(16);

        self::assertNotEquals($bytes1, $bytes2);
    }

    /**
     * Test uniqueIdWithDate generates with prefix.
     */
    public function testUniqueIdWithDateWithPrefix(): void
    {
        $uniqueId = $this->randomService->uniqueIdWithDate('TEST');

        self::assertStringStartsWith('TEST', $uniqueId);
        self::assertLessThanOrEqual(35, strlen($uniqueId));
    }

    /**
     * Test uniqueIdWithDate generates without prefix.
     */
    public function testUniqueIdWithDateWithoutPrefix(): void
    {
        $uniqueId = $this->randomService->uniqueIdWithDate(null);

        self::assertNotEmpty($uniqueId);
        self::assertLessThanOrEqual(35, strlen($uniqueId));
    }

    /**
     * Test uniqueIdWithDate contains date components.
     */
    public function testUniqueIdWithDateContainsDate(): void
    {
        $uniqueId = $this->randomService->uniqueIdWithDate('PRFX');

        // Should contain year (4 digits) after prefix
        self::assertMatchesRegularExpression('/^PRFX\d{4}/', $uniqueId);
    }

    /**
     * Test uniqueIdWithDate generates unique IDs.
     */
    public function testUniqueIdWithDateGeneratesUniqueIds(): void
    {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = $this->randomService->uniqueIdWithDate('TEST');
        }

        self::assertEquals(count($ids), count(array_unique($ids)));
    }
}
