<?php

namespace Phlib\Tests\Mail\Content;

use Phlib\Mail\Content\Attachment;

class AttachmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Attachment
     */
    private $part;

    protected function setUp()
    {
        $this->part = new Attachment();
    }

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

    public function testGetTypeDefault()
    {
        $part = new Attachment();
        $this->assertEquals('application/octet-stream', $part->getType());
    }

    public function testSetGetType()
    {
        $type = 'text/plain';
        $part = new Attachment($type);
        $this->assertEquals($type, $part->getType());
    }

    public function testSetEncoding()
    {
        $encoding = 'base64';
        $this->part->setEncoding($encoding);

        $this->assertEquals($encoding, $this->part->getEncoding());
    }

    public function testSetEncodingDefault()
    {
        $encoding = 'base64';
        $this->assertEquals($encoding, $this->part->getEncoding());
    }

    /**
     * @dataProvider dataSetGetEncodingInvalid
     * @expectedException \InvalidArgumentException
     */
    public function testSetGetEncodingInvalid($encoding)
    {
        $this->part->setEncoding($encoding);
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

    public function testSetName()
    {
        $filename = 'example-file-name.png';
        $this->part->setName($filename);
        // Need to set disposition, so can check the name appears there too
        $this->part->setDisposition('attachment');

        $actual = $this->part->getEncodedHeaders();
        $this->assertRegExp("/Content-Type: [^;]+; name=\"{$filename}\"/", $actual);
        $this->assertRegExp("/Content-Disposition: [^;]+; filename=\"{$filename}\"/", $actual);
    }

    /**
     * @expectedException \Phlib\Mail\Exception\RuntimeException
     * @expectedExceptionMessage name must be defined
     */
    public function testNoSetName()
    {
        $this->part->getEncodedHeaders();
    }

    public function testSetDisposition()
    {
        $disposition = 'example-disposition';
        $this->part->setDisposition($disposition);
        // Need to set name, to avoid validation exception
        $this->part->setName('example-file-name.png');

        $actual = $this->part->getEncodedHeaders();
        $this->assertContains("Content-Disposition: {$disposition}", $actual);
    }

    public function testNoSetDisposition()
    {
        // Need to set name, to avoid validation exception
        $this->part->setName('example-file-name.png');

        $actual = $this->part->getEncodedHeaders();
        $this->assertNotContains('Content-Disposition:', $actual);
    }

    public function testGetEncodedHeaders()
    {
        $filename = 'example-file-name.png';
        $disposition = 'attachment';
        $this->part->setName($filename);
        $this->part->setDisposition($disposition);

        $expected = "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: {$disposition}; filename=\"{$filename}\"\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }
}
