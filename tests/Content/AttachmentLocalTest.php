<?php

namespace Phlib\Tests\Mail\Content;

use Phlib\Mail\Content\AttachmentLocal;

class AttachmentLocalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttachmentLocal
     */
    protected $part;

    /**
     * @var string
     */
    protected $testFile = '';

    protected function setUp()
    {
        $this->part = new AttachmentLocal();

        $this->testFile = realpath(__DIR__ . '/../__files/attachments_expected_attch1.txt');
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

    public function testNameIsSet()
    {
        $this->part->setFile($this->testFile);
        $this->part->setDisposition('attachment');
        $filename = basename($this->testFile);

        $expected = "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: attachment; filename=\"{$filename}\"\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }
}
