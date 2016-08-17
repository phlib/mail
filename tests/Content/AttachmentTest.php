<?php

namespace Phlib\Tests\Mail\Content;

use Phlib\Mail\Content\Attachment;

class AttachmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Attachment
     */
    protected $part;

    /**
     * @var string
     */
    protected $testFile = '';

    protected function setUp()
    {
        $this->part = new Attachment();

        $this->testFile = realpath(__DIR__ . '/../__files/attachments_expected_attch1.txt');
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

    public function testSetFileGetContent()
    {
        $this->part->setFile($this->testFile);

        $expected = file_get_contents($this->testFile);
        $this->assertEquals($expected, $this->part->getContent());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetContentNoFile()
    {
        $this->assertEquals(false, $this->part->getContent());
    }

    public function testGetEncodedHeaders()
    {
        $this->part->setFile($this->testFile);
        $type     = 'application/octet-stream';
        $encoding = 'base64';
        $filename = basename($this->testFile);

        $expected = "Content-Type: $type; charset=\"UTF-8\"; name=\"$filename\"\r\n"
            . "Content-Transfer-Encoding: $encoding\r\n"
            . "Content-Disposition: attachment; filename=\"$filename\"\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }
}
