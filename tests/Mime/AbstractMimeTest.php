<?php
declare(strict_types=1);

namespace Phlib\Mail\Tests\Mime;

use Phlib\Mail\Mime\AbstractMime;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class AbstractMimeTest extends TestCase
{
    use PHPMock;

    /**
     * @var AbstractMime
     */
    protected $part;

    protected function setUp(): void
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

    /**
     * When the boundary is created, it must not match any existing content
     *
     * Test is run in separate process as the mock needed for md5() cannot be declared early enough
     * @runInSeparateProcess
     */
    public function testBoundaryIsUnique()
    {
        $boundary1 = 'test1boundary';
        $boundary2 = 'test2boundary';
        $expected = 'finalboundary';

        $textPart = new \Phlib\Mail\Content\Text();
        $contentText = $boundary1 . ' ' . $boundary2;
        $textPart->setContent($contentText);
        $this->part->setParts([$textPart]);

        // Mock the md5() function, so the test can control the boundary being created
        $mockMd5 = $this->getFunctionMock('Phlib\Mail\Mime', 'md5');
        $mockMd5->expects(static::exactly(3))
            ->willReturnOnConsecutiveCalls($boundary1, $boundary2, $expected);

        // Boundary is generated during toString()
        $this->part->toString();

        $actual = $this->part->getBoundary();

        $this->assertEquals($expected, $actual);
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
        $this->assertEquals([], $this->part->getParts());
    }

    public function testGetPartsDefault()
    {
        $this->assertEquals([], $this->part->getParts());
    }

    /**
     * @covers \Phlib\Mail\Mime\AbstractMime::toString
     * @uses \Phlib\Mail\Content\AbstractContent<extended>
     * @uses \Phlib\Mail\Mime\AbstractMime<extended>
     */
    public function testToString()
    {
        $expected = [];

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
