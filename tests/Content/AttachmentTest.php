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

    public function testGetTypeDefault()
    {
        $type = "application/octet-stream";
        $this->assertEquals($type, $this->part->getType());
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

    public function testGetEncodedHeaders()
    {
        $type     = 'application/octet-stream';
        $encoding = 'base64';
        $filename = 'example-file-name.png';
        $this->part->setName($filename);

        $expected = "Content-Type: $type; name=\"$filename\"\r\n"
            . "Content-Transfer-Encoding: $encoding\r\n"
            . "Content-Disposition: attachment; filename=\"$filename\"\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }
}
