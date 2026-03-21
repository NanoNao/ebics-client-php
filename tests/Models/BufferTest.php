<?php

namespace EbicsApi\Ebics\Tests\Models;

use EbicsApi\Ebics\Models\Buffer;
use PHPUnit\Framework\TestCase;

/**
 * Class BufferTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
class BufferTest extends TestCase
{
    private string $filename;

    protected function setUp(): void
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'ebics_buffer_test');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    public function testBufferLifecycle(): void
    {
        $buffer = new Buffer($this->filename);
        $buffer->open('w+');

        $buffer->write('Hello World');
        $this->assertEquals(11, $buffer->length());

        $buffer->rewind();
        $this->assertEquals(11, $buffer->length()); // Length should be preserved after rewind (based on fstat)

        $content = $buffer->read(5);
        $this->assertEquals('Hello', $content);
        $this->assertEquals(6, $buffer->length()); // Length decreases as we read? Wait, let's check implementation.

        $content = $buffer->read(); // Default length
        $this->assertEquals(' World', $content);
        $this->assertEquals(0, $buffer->length());

        $this->assertTrue($buffer->eof());

        $buffer->close();
    }

    public function testReadContent(): void
    {
        file_put_contents($this->filename, 'Test Content');

        $buffer = new Buffer($this->filename);
        $buffer->open('r');

        $buffer->rewind(); // Initialize length
        $this->assertEquals(12, $buffer->length());

        $content = $buffer->readContent();
        $this->assertEquals('Test Content', $content);
        $this->assertEquals(0, $buffer->length());

        $buffer->close();
    }

    public function testFseek(): void
    {
        file_put_contents($this->filename, '0123456789');

        $buffer = new Buffer($this->filename);
        $buffer->open('r');

        $buffer->fseek(5);
        $content = $buffer->read(1);
        $this->assertEquals('5', $content);

        $buffer->close();
    }

    public function testFilterAppend(): void
    {
        $buffer = new Buffer($this->filename);
        $buffer->open('w+');

        $buffer->filterAppend('convert.base64-encode', STREAM_FILTER_WRITE);
        $buffer->write('Hello');

        $buffer->rewind();
        $buffer->close();

        $content = file_get_contents($this->filename);
        $this->assertEquals(base64_encode('Hello'), trim($content));
    }
}
