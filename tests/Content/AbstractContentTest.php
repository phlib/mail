<?php

declare(strict_types=1);

namespace Phlib\Mail\Tests\Content;

use Phlib\Mail\AbstractPart;
use Phlib\Mail\Content\AbstractContent;
use PHPUnit\Framework\TestCase;

class AbstractContentTest extends TestCase
{
    /**
     * @var AbstractContent
     */
    protected $part;

    protected function setUp(): void
    {
        $this->part = $this->getMockForAbstractClass(AbstractContent::class);
    }

    public function testSetGetContent()
    {
        $content = "some content\r\nsome more content";
        $this->part->setContent($content);

        $this->assertEquals($content, $this->part->getContent());
    }

    public function testSetGetCharset()
    {
        $charset = 'utf8';
        $this->part->setCharset($charset);

        $this->assertEquals($charset, $this->part->getCharset());
    }

    /**
     * @dataProvider dataEncodeContent
     */
    public function testEncodeContent($encoding, $value, $expected)
    {
        $this->part->setEncoding($encoding);
        $actual = $this->part->encodeContent($value);
        $this->assertEquals($expected, $actual);
    }

    public function dataEncodeContent()
    {
        $value = "line1\r\n"
            . "line2, high ascii > é <\r\n";

        $b64 = 'bGluZTENCmxpbmUyLCBoaWdoIGFzY2lpID4gw6kgPA0K';

        $qp = "line1\r\n"
            . "line2, high ascii > =C3=A9 <\r\n";

        $bit7 = "line1\r\n"
            . "line2, high ascii >  <\r\n";

        $bit8 = "line1\r\n"
            . "line2, high ascii >  <\r\n";

        return [
            [AbstractPart::ENCODING_BASE64, $value, $b64],
            [AbstractPart::ENCODING_QPRINTABLE, $value, $qp],
            [AbstractPart::ENCODING_7BIT, $value, $bit7],
            [AbstractPart::ENCODING_8BIT, $value, $bit8]
        ];
    }

    public function testToString()
    {
        $this->part->addHeader('test', 'value');

        $content = "some content\r\nsome more content";
        $this->part->setContent($content);

        $expected = "Test: value\r\n\r\n"  . $content;
        $this->assertEquals($expected, $this->part->toString());
    }
}
