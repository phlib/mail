<?php

namespace Phlib\Tests\Mail\Mime;

use Phlib\Mail\Mime\AbstractMime;

class AbstractMimeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractMime
     */
    protected $part;

    protected function setUp()
    {
        $this->part = $this->getMockForAbstractClass(AbstractMime::class);
    }

    public function testGetBoundary()
    {
        // Boundary is generated during toString()
        $this->part->toString();

        $actual = $this->part->getBoundary();

        $this->assertNotNull($actual);
        $this->assertStringMatchesFormat('%x', $actual);
        $this->assertEquals(32, strlen($actual));
    }

    public function testGetBoundaryDefault()
    {
        $boundary = null;
        $this->assertEquals($boundary, $this->part->getBoundary());
    }

    public function testAddPart()
    {
        $htmlPart = new \Phlib\Mail\Content\Html();
        $textPart = new \Phlib\Mail\Content\Text();

        $this->part->addPart($htmlPart);
        $this->part->addPart($textPart);

        $expected = [
            $htmlPart,
            $textPart
        ];
        $this->assertEquals($expected, $this->part->getParts());
    }

    public function testSetParts()
    {
        $partBefore = new \Phlib\Mail\Content\Html();
        $this->part->addPart($partBefore);
        $expectedBefore = [$partBefore];
        $this->assertEquals($expectedBefore, $this->part->getParts());

        $htmlPart = new \Phlib\Mail\Content\Html();
        $textPart = new \Phlib\Mail\Content\Text();

        $parts = [
            $htmlPart,
            $textPart
        ];
        $this->part->setParts($parts);

        $this->assertEquals($parts, $this->part->getParts());
    }

    public function testClearParts()
    {
        $contentBefore = new \Phlib\Mail\Content\Html();
        $this->part->addPart($contentBefore);
        $expectedBefore = [$contentBefore];
        $this->assertEquals($expectedBefore, $this->part->getParts());

        $this->part->clearParts();
        $this->assertEquals(array(), $this->part->getParts());
    }

    public function testGetPartsDefault()
    {
        $this->assertEquals(array(), $this->part->getParts());
    }

    /**
     * @covers \Phlib\Mail\Mime\AbstractMime::toString
     * @uses \Phlib\Mail\Content\AbstractContent<extended>
     * @uses \Phlib\Mail\Mime\AbstractMime<extended>
     */
    public function testToString()
    {
        $expected = array();

        $this->part->addHeader('test-header', 'header value');
        $expected[] = "Test-Header: header value\r\n";

        $htmlPart = new \Phlib\Mail\Content\Html();
        $contentHtml = '<b>HTML Content</b>';
        $htmlPart->setContent($contentHtml);
        $htmlPart->setCharset('UTF-8');
        $expected[] = "Content-Type: text/html; charset=\"UTF-8\"\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n"
            . "\r\n{$contentHtml}";

        $textPart = new \Phlib\Mail\Content\Text();
        $contentText = 'Text Content';
        $textPart->setContent($contentText);
        $textPart->setCharset('UTF-8');
        $expected[] = "Content-Type: text/plain; charset=\"UTF-8\"\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n"
            . "\r\n{$contentText}";

        $parts = [
            $htmlPart,
            $textPart
        ];
        $this->part->setParts($parts);

        $actual = $this->part->toString();

        $boundary = $this->part->getBoundary();
        $expected = implode("\r\n--{$boundary}\r\n", $expected);
        $expected .= "\r\n--{$boundary}--\r\n";

        $this->assertEquals($expected, $actual);
    }
}
