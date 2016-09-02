<?php

namespace Phlib\Tests\Mail\Content;

use Phlib\Mail\Content\Attachment;

class AttachmentTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromFile()
    {
        $filename = realpath(__DIR__ . '/../__files/attachments_expected_attch1.txt');
        $basename = basename($filename);
        $part = Attachment::createFromFile($filename);

        // Type
        $expectedType = 'text/plain';
        $this->assertEquals($expectedType, $part->getType());

        // Content
        $content = file_get_contents($filename);
        $this->assertEquals($content, $part->getContent());

        // Name
        $expected = "Content-Type: {$expectedType}; name=\"{$basename}\"\r\n"
                    . "Content-Transfer-Encoding: base64\r\n"
                    . "Content-Disposition: attachment; filename=\"{$basename}\"\r\n";
        // Need to set disposition, so can check the name appears there too
        $part->setDisposition('attachment');

        $actual = $part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Phlib\Mail\Exception\InvalidArgumentException
     * @expectedExceptionMessage file cannot be read
     */
    public function testCreateFromFileInvalid()
    {
        Attachment::createFromFile('path/to/invalid/file.txt');
    }

    public function testGetTypeDefault()
    {
        $part = new Attachment('example-file-name.png');
        $this->assertEquals('application/octet-stream', $part->getType());
    }

    public function testSetGetType()
    {
        $type = 'text/plain';
        $part = new Attachment('example-file-name.png', $type);
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
     * @expectedException \InvalidArgumentException
     */
    public function testSetGetEncodingInvalid($encoding)
    {
        $part = new Attachment('example-file-name.png');
        $part->setEncoding($encoding);
    }

    public function dataSetGetEncodingInvalid()
    {
        return [
            ['quoted-printable'],
            ['7bit'],
            ['8bit'],
            ['invalid-encoding']
        ];
    }

    public function testConstructName()
    {
        $filename = 'example-file-name.png';
        $part = new Attachment($filename);

        // Need to set disposition, so can check the name appears there too
        $part->setDisposition('attachment');

        $actual = $part->getEncodedHeaders();
        $this->assertRegExp("/Content-Type: [^;]+; name=\"{$filename}\"/", $actual);
        $this->assertRegExp("/Content-Disposition: [^;]+; filename=\"{$filename}\"/", $actual);
    }

    public function testSetName()
    {
        $part = new Attachment('original-file-name.png');
        $filename = 'example-file-name.png';
        $part->setName($filename);

        // Need to set disposition, so can check the name appears there too
        $part->setDisposition('attachment');

        $actual = $part->getEncodedHeaders();
        $this->assertRegExp("/Content-Type: [^;]+; name=\"{$filename}\"/", $actual);
        $this->assertRegExp("/Content-Disposition: [^;]+; filename=\"{$filename}\"/", $actual);
    }

    public function testSetDisposition()
    {
        $disposition = 'example-disposition';
        $part = new Attachment('example-file-name.png');
        $part->setDisposition($disposition);

        $actual = $part->getEncodedHeaders();
        $this->assertContains("Content-Disposition: {$disposition}", $actual);
    }

    public function testNoSetDisposition()
    {
        $part = new Attachment('example-file-name.png');

        $actual = $part->getEncodedHeaders();
        $this->assertNotContains('Content-Disposition:', $actual);
    }

    public function testGetEncodedHeaders()
    {
        $filename = 'example-file-name.png';
        $disposition = 'attachment';

        $part = new Attachment($filename);
        $part->setDisposition($disposition);

        $expected = "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: {$disposition}; filename=\"{$filename}\"\r\n";

        $actual = $part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }
}
