<?php

declare(strict_types=1);

namespace Phlib\Mail\Tests\Content;

use Phlib\Mail\AbstractPart;
use Phlib\Mail\Content\Attachment;
use Phlib\Mail\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    public function testCreateFromFile()
    {
        $filename = realpath(__DIR__ . '/../__files/attachments-expected-attch1.txt');
        $basename = basename($filename);
        $part = Attachment::createFromFile($filename, 'attachment');

        // Type
        $expectedType = 'text/plain';
        $this->assertEquals($expectedType, $part->getType());

        // Content
        $content = file_get_contents($filename);
        $this->assertEquals($content, $part->getContent());

        // Name
        $expected = "Content-Type: {$expectedType}; name={$basename}\r\n"
                    . "Content-Transfer-Encoding: base64\r\n"
                    . "Content-Disposition: attachment; filename={$basename}\r\n";

        $actual = $part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }

    public function testCreateFromFileInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file cannot be read');

        Attachment::createFromFile('path/to/invalid/file.txt');
    }

    public function testGetTypeDefault()
    {
        $part = new Attachment('example-file-name.png');
        $this->assertEquals('application/octet-stream', $part->getType());
    }

    public function testGetTypeDefaultExplicitNull()
    {
        $type = null;
        $part = new Attachment('example-file-name.png', null, $type);
        $this->assertEquals('application/octet-stream', $part->getType());
    }

    public function testSetGetType()
    {
        $type = 'text/plain';
        $part = new Attachment('example-file-name.png', null, $type);
        $this->assertEquals($type, $part->getType());
    }

    public function testSetEncoding()
    {
        $encoding = 'base64';
        $part = new Attachment('example-file-name.png');
        $part->setEncoding($encoding);

        $this->assertEquals($encoding, $part->getEncoding());
    }

    public function testSetEncodingDefault()
    {
        $encoding = 'base64';
        $part = new Attachment('example-file-name.png');
        $this->assertEquals($encoding, $part->getEncoding());
    }

    /**
     * @dataProvider dataSetGetEncodingInvalid
     */
    public function testSetGetEncodingInvalid($encoding)
    {
        $this->expectException(InvalidArgumentException::class);
        $part = new Attachment('example-file-name.png');
        $part->setEncoding($encoding);
    }

    public function dataSetGetEncodingInvalid()
    {
        return [
            [AbstractPart::ENCODING_QPRINTABLE],
            [AbstractPart::ENCODING_7BIT],
            [AbstractPart::ENCODING_8BIT],
            ['invalid-encoding']
        ];
    }

    public function testConstructNameDisposition()
    {
        $filename = 'example-file-name.png';
        $disposition = 'attachment';
        $part = new Attachment($filename, $disposition, 'application/octet-stream');

        $actual = $part->getEncodedHeaders();
        $this->assertRegExp("/Content-Type: [^;]+; name={$filename}/", $actual);
        $this->assertRegExp("/Content-Disposition: {$disposition}; filename={$filename}/", $actual);
    }

    public function testNoDispositionDefault()
    {
        $part = new Attachment('example-file-name.png');

        $actual = $part->getEncodedHeaders();
        $this->assertStringNotContainsStringIgnoringCase('Content-Disposition:', $actual);
    }

    public function testNoDispositionNull()
    {
        $part = new Attachment('example-file-name.png', null);

        $actual = $part->getEncodedHeaders();
        $this->assertStringNotContainsStringIgnoringCase('Content-Disposition:', $actual);
    }

    public function testGetEncodedHeaders()
    {
        $filename = 'example-file-name.png';
        $disposition = 'attachment';

        $part = new Attachment($filename, $disposition);

        $expected = "Content-Type: application/octet-stream; name={$filename}\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: {$disposition}; filename={$filename}\r\n";

        $actual = $part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }
}
