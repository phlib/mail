<?php

declare(strict_types=1);

namespace Phlib\Mail\Tests;

use Phlib\Mail\AbstractPart;
use Phlib\Mail\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AbstractPartTest extends TestCase
{
    /**
     * @var AbstractPart
     */
    protected $part;

    protected function setUp(): void
    {
        $this->part = $this->getMockForAbstractClass(AbstractPart::class);
    }

    public function testAddGetHeader()
    {
        $this->part->addHeader('test', 'value1');
        $this->part->addHeader('test', 'value2');

        $expected = ['value1', 'value2'];
        $actual = $this->part->getHeader('test');

        $this->assertEquals($expected, $actual);
    }

    public function testAddHeaderFilterValue()
    {
        $this->part->addHeader('test', "va\rl\nu\te");
        $expected = ['value'];
        $actual = $this->part->getHeader('test');
        $this->assertEquals($expected, $actual);
    }

    public function testAddHeaderInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid name');

        $this->part->addHeader('invalid name', 'value');
    }

    /**
     * @dataProvider dataAddHeaderProhibitedName
     */
    public function testAddHeaderProhibitedName($name)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name is prohibited');

        $this->part->addHeader($name, 'value');
    }

    public function dataAddHeaderProhibitedName()
    {
        return [
            ['content-type'],
            ['content-transfer-encoding'],
            ['mime-version']
        ];
    }

    public function testSetHeader()
    {
        $this->part->addHeader('test', 'value1');
        $expectedBefore = ['value1'];
        $actualBefore = $this->part->getHeader('test');
        $this->assertEquals($expectedBefore, $actualBefore);

        $this->part->setHeader('test', 'value2');
        $expectedAfter = ['value2'];
        $actualAfter = $this->part->getHeader('test');
        $this->assertEquals($expectedAfter, $actualAfter);
    }

    public function testClearHeader()
    {
        $this->part->addHeader('test', 'value1');
        $expectedBefore = ['value1'];
        $actualBefore = $this->part->getHeader('test');
        $this->assertEquals($expectedBefore, $actualBefore);

        $this->part->clearHeader('test');
        $expectedAfter = [];
        $actualAfter = $this->part->getHeader('test');
        $this->assertEquals($expectedAfter, $actualAfter);
    }

    public function testRemoveHeader()
    {
        $this->part->addHeader('test', 'value1');
        $this->part->addHeader('test', 'value2');
        $expectedBefore = ['value1', 'value2'];
        $actualBefore = $this->part->getHeader('test');
        $this->assertEquals($expectedBefore, $actualBefore);

        $this->part->removeHeader('test', 'value1');
        $actualAfter = $this->part->getHeader('test');
        $this->assertNotContains('value1', $actualAfter);
        $this->assertContains('value2', $actualAfter);

        $this->part->removeHeader('test', 'value2');
        $this->assertEmpty($this->part->getHeader('test'));
        $this->assertFalse($this->part->hasHeader('test'));
    }

    public function testGetHeaders()
    {
        $expected = $this->addHeaders();

        $actual = $this->part->getHeaders();
        $this->assertEquals($expected[0], $actual);
    }

    public function testClearHeaders()
    {
        $expected = $this->addHeaders();

        $actualBefore = $this->part->getHeaders();
        $this->assertEquals($expected[0], $actualBefore);

        $this->part->clearHeaders();
        $actualAfter = $this->part->getHeaders();
        $this->assertEquals([], $actualAfter);
    }

    public function testHasHeaderFalse()
    {
        $actual = $this->part->hasHeader('test1');
        $this->assertEquals(false, $actual);
    }

    public function testHasHeaderTrue()
    {
        $this->addHeaders();

        $actual = $this->part->hasHeader('test1');
        $this->assertEquals(true, $actual);
    }

    public function testGetEncodedHeaders()
    {
        // Multiple basic headers
        $expected = $this->addHeaders()[1];

        // Check encoding
        $value = "line1\r\nline2, high ascii > é <\r\n";
        $this->part->addHeader('Subject', $value);
        $expected .= "Subject: line1line2, high ascii > =?UTF-8?B?" . base64_encode('é <') . "?=\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }

    public function testGetEncodedHeadersWhitespace()
    {
        $name = 'From';
        $value = ' "From" <from@mail.example.com> ';
        $this->part->addHeader($name, $value);

        $expected = "$name: " . trim($value) . "\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }

    /**
     * An underscore triggers mb_encode_mimeheader() to encode the string even if not necessary
     */
    public function testGetEncodedHeadersNotEncodedForUnderscore()
    {
        $name = 'Subject';
        $value = 'Latest _offers_';
        $this->part->addHeader($name, $value);

        $expected = "$name: $value\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }

    /**
     * An equals triggers mb_encode_mimeheader() to encode the string even if not necessary
     */
    public function testGetEncodedHeadersNotEncodedForEquals()
    {
        $name = 'From';
        $value = '"From=" <equals@mail.example.com>';
        $this->part->addHeader($name, $value);

        $expected = "$name: $value\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Long header in pure ASCII should not be encoded, but should be wrapped at 78 chars
     * @dataProvider dataGetEncodedHeadersNotEncodedWillSoftWrap
     */
    public function testGetEncodedHeadersNotEncodedWillSoftWrap($name, $valueParts)
    {
        $this->part->addHeader($name, implode(' ', $valueParts));

        $expected = "$name: " . implode("\r\n ", $valueParts) . "\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }

    public function dataGetEncodedHeadersNotEncodedWillSoftWrap()
    {
        return [
            // First line is exactly 78 chars
            ['Received', [
                'by 10.50.76.202 with SMTP id m10mr1877022igw.52.1345128339903; Thu,',
                '16 Aug 2012 07:45:39 -0700 (PDT)'
            ]],
            // Test multiple lines
            ['Received', [
                'from mail-yx0-f181.google.com (mail-yx0-f181.google.com',
                '[209.85.213.181]) by mail.example.com (Postfix) with ESMTPS id 92F4A161D85',
                'for <recipient@example.com>; Thu, 16 Aug 2012 15:45:41 +0100 (BST)'
            ]],
            // Test very long
            ['Dkim-Signature', [
                'v=1; a=rsa-sha256; c=relaxed/relaxed; d=gmail.com; s=20120113;',
                'h=mime-version:x-goomoji-body:date:message-id:subject:from:to:',
                'content-type;bh=axdcMS9VSIT9/g8h/o69GDtb4N1cYQ2rUOrvm7/46DU=;',
                'b=zw0iOrFoyB1gn/qiFdguXs4OM7UB0d4kT6OOBq8JY/1BQAlS9j+itqA+nezoFg84a3',
                'ONxbn4my2RZLv9SSKYRsNr+SOMPsEAjNJJGoWacE7/JmW7iVCWpGB0co7Ejxhr3EwUM0',
                'G2fZB7/cQrV7zYIrkkoetRWYTqTvOt7W8lfEJaLXFOSATqW/Xcaos5BWo88rJImDWrew',
                '1k3YbnNs0jyXvPO+jytUfWEkDPu7w1k+K9TqvHtGeawyj21QeNmo1Z1P//g29MO61m/N',
                'bU+IexdOG/O4XcauU1Qk8gGm0xA3szGZXGaaji8eBgknY8E6bxNItIiDaJ9vHGLvyMZj6SGg=='
            ]]
        ];
    }

    /**
     * Special handling for Content-Type, adds charset, encoding and additional params if available
     * @dataProvider dataGetEncodedHeadersContentType
     */
    public function testGetEncodedHeadersContentType(
        ?string $type,
        ?string $charset,
        string $encoding,
        ?string $typeParam,
        string $expected
    ): void {
        // Use anon class to set the immutable type and add additional params
        $part = new class ($type, $typeParam) extends AbstractPart {
            private $typeParam;
            public function __construct(?string $type, ?string $typeParam)
            {
                $this->type = $type;
                $this->typeParam = $typeParam;
            }
            public function toString(): string
            {
                return '';
            }
            protected function addContentTypeParameters(string $contentType): string
            {
                if ($this->typeParam) {
                    $contentType .= "; {$this->typeParam}";
                }
                return $contentType;
            }
        };

        if ($charset) {
            $part->setCharset($charset);
        }
        if ($encoding) {
            $part->setEncoding($encoding);
        }

        $actual = $part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }

    public function dataGetEncodedHeadersContentType(): iterable
    {
        $types = [
            null,
            'text/html',
            'application/octet-stream',
        ];
        $charsets = [
            null,
            'UTF-8',
            'ascii',
        ];
        $encodings = [
            // Encoding must be allowed type, `null` not allowed
            AbstractPart::ENCODING_QPRINTABLE,
            AbstractPart::ENCODING_BASE64,
        ];
        $typeParams = [
            null,
            'attr=value',
        ];

        foreach ($types as $type) {
            foreach ($charsets as $charset) {
                foreach ($encodings as $encoding) {
                    foreach ($typeParams as $typeParam) {
                        $name = ($type ?? 'NULL') . ' ' .
                            ($charset ?? 'NULL') . ' ' .
                            ($encoding ?? 'NULL') . ' ' .
                            ($typeParam ?? 'NULL');
                        $expected = '';
                        if ($type !== null) {
                            $expected = "Content-Type: {$type}";

                            if ($charset !== null) {
                                $expected .= "; charset=\"{$charset}\"";
                            }
                            if ($typeParam !== null) {
                                $expected .= "; {$typeParam}";
                            }
                            $expected .= "\r\n";

                            if ($encoding !== null) {
                                $expected .= "Content-Transfer-Encoding: {$encoding}\r\n";
                            }
                        }
                        yield $name => [$type, $charset, $encoding, $typeParam, $expected];
                    }
                }
            }
        }
    }

    /**
     * @dataProvider dataSetGetEncodingValid
     */
    public function testSetGetEncodingValid($encoding)
    {
        $this->part->setEncoding($encoding);

        $this->assertEquals($encoding, $this->part->getEncoding());
    }

    public function dataSetGetEncodingValid()
    {
        return [
            [AbstractPart::ENCODING_BASE64],
            [AbstractPart::ENCODING_QPRINTABLE],
            [AbstractPart::ENCODING_7BIT],
            [AbstractPart::ENCODING_8BIT]
        ];
    }

    /**
     * @dataProvider dataSetGetEncodingInvalid
     */
    public function testSetGetEncodingInvalid(string $encoding): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->part->setEncoding($encoding);
    }

    public function dataSetGetEncodingInvalid(): iterable
    {
        return [
            ['invalid-encoding'],
            [''],
        ];
    }

    public function testGetTypeDefault()
    {
        $type = null;
        $this->assertEquals($type, $this->part->getType());
    }

    /**
     * Add headers to the mail object and return the expected header string
     *
     * @return array The expected header array and string
     */
    protected function addHeaders()
    {
        $data = [
            'test1' => [
                'value1',
                'value2'
            ],
            'test2' => [
                'value3',
                'value4'
            ]
        ];

        $expected = '';
        foreach ($data as $name => $values) {
            foreach ($values as $value) {
                $name = ucwords($name);
                $this->part->addHeader(ucwords($name), $value);
                $expected .= "{$name}: {$value}\r\n";
            }
        }

        return [$data, $expected];
    }
}
