<?php

namespace EbicsApi\Ebics\Tests\Models\Crypt;

use EbicsApi\Ebics\Models\Crypt\TripleDES;
use PHPUnit\Framework\TestCase;

/**
 * Class TripleDESTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
class TripleDESTest extends TestCase
{
    public function testEncryptDecrypt(): void
    {
        $tripleDES = new TripleDES();

        $key = '123456789012345678901234';
        $iv = '12345678';

        $tripleDES->setKey($key);
        $tripleDES->setIV($iv);

        $plaintext = 'Hello World';

        $ciphertext = $tripleDES->encrypt($plaintext);
        $this->assertNotEquals($plaintext, $ciphertext);
        $this->assertNotEmpty($ciphertext);

        $decrypted = $tripleDES->decrypt($ciphertext);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testDecryptWithWrongKeyFails(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Decryption failed');

        $tripleDES = new TripleDES();

        $key = '123456789012345678901234';
        $iv = '12345678';
        $tripleDES->setKey($key);
        $tripleDES->setIV($iv);

        $ciphertext = $tripleDES->encrypt('Hello');

        $wrongKey = '432109876543210987654321';
        $tripleDES->setKey($wrongKey);

        $tripleDES->decrypt($ciphertext);
    }
}
