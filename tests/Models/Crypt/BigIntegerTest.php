<?php

namespace EbicsApi\Ebics\Tests\Models\Crypt;

use EbicsApi\Ebics\Models\Crypt\BigInteger;
use PHPUnit\Framework\TestCase;

/**
 * Class BigIntegerTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
class BigIntegerTest extends TestCase
{
    public function testConstructAndToString(): void
    {
        $bi = new BigInteger(12345);
        $this->assertEquals('12345', $bi->toString());
        $this->assertEquals('12345', (string)$bi);

        $bi = new BigInteger('12345');
        $this->assertEquals('12345', $bi->toString());

        $bi = new BigInteger('-12345');
        $this->assertEquals('-12345', $bi->toString());

        $bi = new BigInteger('abc', 256);
        $this->assertEquals('6382179', $bi->toString());

        $bi = new BigInteger("\xFF", -256);
        $this->assertEquals('-1', $bi->toString());
    }

    public function testToBytes(): void
    {
        $bi = new BigInteger('6382179'); // "abc"
        $this->assertEquals('abc', $bi->toBytes());

        $bi = new BigInteger('0');
        $this->assertEquals('', $bi->toBytes());

        $bi = new BigInteger('-1');

        $this->assertEquals("\xFF", $bi->toBytes(true));
    }

    public function testToHex(): void
    {
        $bi = new BigInteger('255');
        $this->assertEquals('ff', $bi->toHex());
    }

    public function testEquals(): void
    {
        $bi1 = new BigInteger(100);
        $bi2 = new BigInteger(100);
        $bi3 = new BigInteger(101);

        $this->assertTrue($bi1->equals($bi2));
        $this->assertFalse($bi1->equals($bi3));
    }

    public function testCompare(): void
    {
        $bi1 = new BigInteger(100);
        $bi2 = new BigInteger(200);

        $this->assertEquals(-1, $bi1->compare($bi2));
        $this->assertEquals(1, $bi2->compare($bi1));
        $this->assertEquals(0, $bi1->compare(new BigInteger(100)));
    }

    public function testAdd(): void
    {
        $bi1 = new BigInteger(10);
        $bi2 = new BigInteger(20);
        $result = $bi1->add($bi2);

        $this->assertEquals('30', $result->toString());
    }

    public function testSubtract(): void
    {
        $bi1 = new BigInteger(30);
        $bi2 = new BigInteger(10);
        $result = $bi1->subtract($bi2);

        $this->assertEquals('20', $result->toString());
    }

    public function testMultiply(): void
    {
        $bi1 = new BigInteger(10);
        $bi2 = new BigInteger(20);
        $result = $bi1->multiply($bi2);

        $this->assertEquals('200', $result->toString());
    }

    public function testDivide(): void
    {
        $bi1 = new BigInteger(100);
        $bi2 = new BigInteger(3);
        [$quotient, $remainder] = $bi1->divide($bi2);

        $this->assertEquals('33', $quotient->toString());
        $this->assertEquals('1', $remainder->toString());
    }

    public function testModPow(): void
    {
        $base = new BigInteger(2);
        $exp = new BigInteger(3);
        $mod = new BigInteger(5);

        $result = $base->modPow($exp, $mod);
        $this->assertEquals('3', $result->toString());
    }

    public function testModInverse(): void
    {
        $num = new BigInteger(3);
        $mod = new BigInteger(11);

        $result = $num->modInverse($mod);
        $this->assertEquals('4', $result->toString());
    }

    public function testAbs(): void
    {
        $bi = new BigInteger('-100');
        $this->assertEquals('100', $bi->abs()->toString());

        $bi = new BigInteger('100');
        $this->assertEquals('100', $bi->abs()->toString());
    }

    public function testBitwiseLeftShift(): void
    {
        $bi = new BigInteger(5);
        $result = $bi->bitwiseLeftShift(1);
        $this->assertEquals('10', $result->toString());
    }

    public function testBitwiseRightShift(): void
    {
        $bi = new BigInteger(10);
        $result = $bi->bitwiseRightShift(1);
        $this->assertEquals('5', $result->toString());
    }

    public function testBitwiseOr(): void
    {
        $bi1 = new BigInteger(5);
        $bi2 = new BigInteger(3);
        $result = $bi1->bitwiseOr($bi2);

        $this->assertEquals('7', $result->toString());
    }

    public function testBitwiseAnd(): void
    {
        $bi1 = new BigInteger(5);
        $bi2 = new BigInteger(3);
        $result = $bi1->bitwiseAnd($bi2);

        $this->assertEquals('1', $result->toString());
    }

    public function testRandom(): void
    {
        $min = new BigInteger(10);
        $max = new BigInteger(20);

        $min->random($min, $max);

        $random = $min->random($min, $max);
        $this->assertTrue($random->compare($min) >= 0);
        $this->assertTrue($random->compare($max) <= 0);

        $random2 = $min->random($max);
        $this->assertTrue($random2->compare($min) >= 0);
        $this->assertTrue($random2->compare($max) <= 0);
    }

    public function testCopy(): void
    {
        $bi = new BigInteger(123);
        $copy = $bi->copy();

        $this->assertEquals($bi->toString(), $copy->toString());
        $this->assertNotSame($bi, $copy);
    }

    public function testSetupPrecision(): void
    {
        $bi = new BigInteger(10);
        $bi->setupPrecision(3);

        $this->assertEquals('2', $bi->toString());
    }
}
