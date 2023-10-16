<?php

declare(strict_types=1);

namespace Phlib\Mail\Tests;

use Phlib\Mail\Exception\RuntimeException;
use Phlib\Mail\Factory;
use Phlib\Mail\Mime\AbstractMime;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    use PHPMock;

    protected function setUp(): void
    {
        $this->defineFunctionMock('\Phlib\Mail', 'mailparse_msg_parse');
        $this->defineFunctionMock('\Phlib\Mail', 'mailparse_msg_parse_file');
        $this->defineFunctionMock('\Phlib\Mail', 'mailparse_msg_get_part');
        $this->defineFunctionMock('\Phlib\Mail', 'mailparse_msg_get_part_data');
        $this->defineFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     * @uses \Phlib\Mail\AbstractPart
     * @uses \Phlib\Mail\Content\AbstractContent
     * @uses \Phlib\Mail\Content\Attachment
     * @uses \Phlib\Mail\Mail
     * @uses \Phlib\Mail\Mime\AbstractMime
     */
    public function testCreateFromFileAttachments()
    {
        $source = __DIR__ . '/__files/attachments-source.eml';

        $mailparse_msg_free = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
        $mailparse_msg_free->expects(self::once())
            ->willReturnCallback(function ($resource) {
                return \mailparse_msg_free($resource);
            });

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        AssertAttachmentsEmail::assertEquals($mail);
        $this->assertEquals(true, $mail->hasAttachment());
        $this->assertEquals(5, $mail->getAttachmentCount());
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     * @uses \Phlib\Mail\Content\Attachment<extended>
     * @uses \Phlib\Mail\Mail
     * @uses \Phlib\Mail\Mime\AbstractMime
     */
    public function testCreateFromStringAttachments()
    {
        $source = __DIR__ . '/__files/attachments-source.eml';

        $mailparse_msg_free = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
        $mailparse_msg_free->expects(self::once())
            ->willReturnCallback(function ($resource) {
                return \mailparse_msg_free($resource);
            });

        $factory = new Factory();
        $mail = $factory->createFromString(file_get_contents($source));

        AssertAttachmentsEmail::assertEquals($mail);
        $this->assertEquals(true, $mail->hasAttachment());
        $this->assertEquals(5, $mail->getAttachmentCount());
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     * @uses \Phlib\Mail\Content\Content<extended>
     * @uses \Phlib\Mail\Mail
     * @uses \Phlib\Mail\Mime\MultipartReport<extended>
     */
    public function testCreateFromFileBounceHead()
    {
        $source = __DIR__ . '/__files/bounce_head-source.eml';

        $mailparse_msg_free = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
        $mailparse_msg_free->expects(self::once())
            ->willReturnCallback(function ($resource) {
                return \mailparse_msg_free($resource);
            });

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        AssertBounceHeadEmail::assertEquals($mail);
        $this->assertEquals(true, $mail->hasAttachment());
        $this->assertEquals(2, $mail->getAttachmentCount());
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     * @uses \Phlib\Mail\Content\Content<extended>
     * @uses \Phlib\Mail\Mail
     * @uses \Phlib\Mail\Mime\MultipartReport<extended>
     */
    public function testCreateFromFileBounceMsg()
    {
        $source = __DIR__ . '/__files/bounce_msg-source.eml';

        $mailparse_msg_free = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
        $mailparse_msg_free->expects(self::once())
            ->willReturnCallback(function ($resource) {
                return \mailparse_msg_free($resource);
            });

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        AssertBounceMsgEmail::assertEquals($mail);
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     * @uses \Phlib\Mail\Content\AbstractContent<extended>
     * @uses \Phlib\Mail\Mail
     */
    public function testCreateFromFileHtml()
    {
        $source = __DIR__ . '/__files/html-source.eml';

        $mailparse_msg_free = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
        $mailparse_msg_free->expects(self::once())
            ->willReturnCallback(function ($resource) {
                return \mailparse_msg_free($resource);
            });

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        AssertHtmlEmail::assertEquals($mail);
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     * @uses \Phlib\Mail\Content\AbstractContent<extended>
     * @uses \Phlib\Mail\Mail
     */
    public function testCreateFromContentAttachment()
    {
        $source = __DIR__ . '/__files/content_attachment-source.eml';

        $mailparse_msg_free = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
        $mailparse_msg_free->expects(self::once())
            ->willReturnCallback(function ($resource) {
                return \mailparse_msg_free($resource);
            });

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        AssertContentAttachmentEmail::assertEquals($mail);
    }

    /**
     * Expect an exception, and there should be NO resource to free
     */
    public function testCreateFromFileNotFound()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be found');

        $source = __DIR__ . '/__files/does-not-exist';

        $mailparse_msg_free = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
        $mailparse_msg_free->expects(self::never());

        $factory = new Factory();
        $factory->createFromFile($source);
    }

    /**
     * Expect an exception, and there should be NO resource to free
     */
    public function testCreateFromFileCannotRead()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('could not be read');

        $source = __DIR__ . '/__files/html-source.eml';

        $mailparse_msg_parse_file = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_parse_file');
        $mailparse_msg_parse_file->expects(self::once())
            ->willReturn(false);

        $mailparse_msg_free = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
        $mailparse_msg_free->expects(self::never());

        $factory = new Factory();
        $factory->createFromFile($source);
    }

    /**
     * Expect an exception, and the created resource should be freed
     */
    public function testCreateFromStringCannotRead()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('could not be read');

        $source = __DIR__ . '/__files/html-source.eml';

        $mailparse_msg_parse = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_parse');
        $mailparse_msg_parse->expects(self::once())
            ->willReturn(false);

        $mailparse_msg_free = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_free');
        $mailparse_msg_free->expects(self::once())
            ->willReturnCallback(function ($resource) {
                return \mailparse_msg_free($resource);
            });

        $factory = new Factory();
        $factory->createFromString(file_get_contents($source));
    }

    public function testAddHeadersDateInvalidTimezone()
    {
        // Date string has double-timezone, not standards-compliant with RFC5322 §3.3. First part is valid.
        $mailString = "Date: Mon, 9 Dec 2019 18:36:20 +0800 (GMT+08:00)\r\n" .
            "\r\n" .
            "plain text\r\n";

        $factory = new Factory();
        $mail = $factory->createFromString($mailString);

        static::assertEquals('2019-12-09T18:36:20+08:00', $mail->getOriginationDate()->format('c'));
    }

    public function testAddHeadersDateInvalidFormat()
    {
        // Date format not valid for RFC5322 §3.3
        $mailString = "Date: 2019-08-03 18:36:20 UTC\r\n" .
            "\r\n" .
            "plain text\r\n";

        $factory = new Factory();
        $mail = $factory->createFromString($mailString);

        static::assertEquals('2019-08-03T18:36:20+00:00', $mail->getOriginationDate()->format('c'));
    }

    public function testAddHeadersDateSpace()
    {
        // Extra space after day for single-digit date
        $mailString = "Date: Mon,  2 May 2016 19:15:14 +0100 (BST)\r\n" .
            "\r\n" .
            "plain text\r\n";

        $factory = new Factory();
        $mail = $factory->createFromString($mailString);

        static::assertEquals('2016-05-02T19:15:14+01:00', $mail->getOriginationDate()->format('c'));
    }

    public function testAddHeadersDateInvalidError()
    {
        // Date format is too wacky to be decoded at all
        $mailString = "Date: nope\r\n" .
            "\r\n" .
            "plain text\r\n";

        $factory = new Factory();
        $mail = $factory->createFromString($mailString);

        static::assertNull($mail->getOriginationDate());
    }

    public function testAddHeadersReceived(): void
    {
        $expected = [
            'from localhost (localhost [127.0.0.1]) by mail.example.com (Postfix) for <recipient@example.com>;' .
                ' Thu, 16 Aug 2012 15:45:43 +0100',
            'from gbnthda3150srv.example.com ([10.67.121.52]) by GBLONVMSX001.nsicorp.int;' .
                ' Thu, 29 Sep 2011 08:48:51 +0100',
        ];

        $mailString = '';
        foreach ($expected as $received) {
            $mailString .= "Received: {$received}\r\n";
        }
        $mailString .= "\r\nplain text\r\n";

        $factory = new Factory();
        $mail = $factory->createFromString($mailString);

        static::assertEquals($expected, $mail->getReceived());
    }

    public function testAddHeadersReceivedDateSpace(): void
    {
        // Extra space after day for single-digit date
        $received = 'from COL004-OMC4S11.outlook.com (col004-omc4s11.outlook.com [65.55.34.213])' .
            ' by mail.example.com (Postfix) with ESMTP id B55877E0585' .
            ' for <bounce-2497-107-151073-received@mxm.mxmfb.com>';
        $originalDate = 'Mon,  2 May 2016 19:15:14 +0100 (BST)';
        $expectedDate = 'Mon, 02 May 2016 19:15:14 +0100';

        $mailString = "Received: {$received}; {$originalDate}\r\n" .
            "\r\nplain text\r\n";

        $factory = new Factory();
        $mail = $factory->createFromString($mailString);

        $expected = "{$received}; {$expectedDate}";
        static::assertEquals($expected, $mail->getReceived()[0]);
    }

    /**
     * Tests for an issue (#10) where the Factory was incorrectly handling emails with 9 child parts, as it would
     * incorrectly try to parse a 10th part (e.g. "1.10") because of non-strict checking for the value "1.10" in the
     * structure array containing a value "1.1"
     */
    public function testNineChildParts()
    {
        $source = __DIR__ . '/__files/mime-9-parts-source.eml';

        $factory = new Factory();

        $mail = $factory->createFromFile($source);

        /** @var AbstractMime $mainPart */
        $mainPart = $mail->getPart();

        $this->assertEquals(9, count($mainPart->getParts()));
    }

    public function testGetPartFail()
    {
        $warningMsg = sha1(uniqid('warning'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse part 1: ' . $warningMsg);

        $mailparse_msg_get_part = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_get_part');
        $mailparse_msg_get_part->expects(static::once())
            ->willReturnCallback(function () use ($warningMsg) {
                trigger_error($warningMsg, E_USER_WARNING);
                return false;
            });

        $source = __DIR__ . '/__files/bounce_msg-source.eml';

        (new Factory())->createFromFile($source);
    }

    public function testGetPartDataFail()
    {
        $warningMsg = sha1(uniqid('warning'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse part 1: ' . $warningMsg);

        $mailparse_msg_get_part_data = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_get_part_data');
        $mailparse_msg_get_part_data->expects(static::exactly(2))
            ->willReturnCallback(function ($part) use ($warningMsg) {
                static $callCount = 0;
                if (++$callCount === 2) {
                    trigger_error($warningMsg, E_USER_WARNING);
                    return false;
                }
                return \mailparse_msg_get_part_data($part);
            });

        $source = __DIR__ . '/__files/bounce_msg-source.eml';

        (new Factory())->createFromFile($source);
    }

    public function testDecodeHeaderUtf8Base64()
    {
        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?=' . "\r\n"
            . ' =?UTF-8?B?dWl0eSBQbGFuIC0gwqMxMDAgZGlzY291bnQgdG9kYXkgb25seSE=?=';

        $expected = [
            'charset' => 'UTF-8',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!',
        ];

        $actual = $this->invokeDecodeHeader($header);
        $this->assertEquals($expected, $actual);
    }

    public function testDecodeHeaderIsoQ()
    {
        $header = '=?ISO-8859-1?Q?London Olympics: Business Continuity Plan - =A3100 discount today only!?=';

        $expected = [
            'charset' => 'ISO-8859-1',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!',
        ];

        $actual = $this->invokeDecodeHeader($header);
        $this->assertEquals($expected, $actual);
    }

    public function testDecodeHeaderPart()
    {
        $header = 'London Olympics: Business Continuity Plan - =?ISO-8859-1?Q?=A3100?= discount today only!';

        $expected = [
            'charset' => 'ISO-8859-1',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!',
        ];

        $actual = $this->invokeDecodeHeader($header);
        $this->assertEquals($expected, $actual);
    }

    public function testDecodeHeaderMixed()
    {
        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?='
            . ' =?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?=';

        $expected = [
            'charset' => 'UTF-8',
            'text' => 'London Olympics: Business ContinKeld Jørn Simonsen',
        ];

        $actual = $this->invokeDecodeHeader($header);
        $this->assertEquals($expected, $actual);
    }

    public function testDecodeBrokenHeader()
    {
        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?=' . "\r\n"
            . ' =?UTF-8?B?dWl0eSBQbGFuIC0gwqPhlibDAgZGlzY291bnQgdG9kYXkgb25seSE=?=';
        $decoded = $this->invokeDecodeHeader($header);
        $this->assertEquals('UTF-8', $decoded['charset']);
        // test that we can at least show something if the encoded word is malformed (RFC 2047 section 6.3)
        $this->assertStringStartsWith('London Olympics: Business Contin', $decoded['text']);
    }

    /**
     * Invoke Factory::decodeHeader()
     *
     * @see Factory::decodeHeader()
     * @param string $header
     * @return array
     */
    private function invokeDecodeHeader($header)
    {
        $mock = $this->createPartialMock(Factory::class, []);

        $method = new \ReflectionMethod(Factory::class, 'decodeHeader');
        $method->setAccessible(true);

        return $method->invoke($mock, $header);
    }
}
