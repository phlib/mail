<?php

namespace Phlib\Mail\Tests;

use Phlib\Mail\Content\Attachment;
use Phlib\Mail\Content\Content;
use Phlib\Mail\Content\Html;
use Phlib\Mail\Content\Text;
use Phlib\Mail\Exception\RuntimeException;
use Phlib\Mail\Factory;
use Phlib\Mail\Mime\AbstractMime;
use Phlib\Mail\Mime\MultipartAlternative;
use Phlib\Mail\Mime\MultipartMixed;
use Phlib\Mail\Mime\MultipartRelated;
use Phlib\Mail\Mime\MultipartReport;
use phpmock\phpunit\PHPMock;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    public function setUp()
    {
        $this->defineFunctionMock('\Phlib\Mail', 'mailparse_msg_get_part');
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
        $source   = __DIR__ . '/__files/attachments-source.eml';

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
        $source   = __DIR__ . '/__files/attachments-source.eml';

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
        $source   = __DIR__ . '/__files/bounce_head-source.eml';

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
        $source   = __DIR__ . '/__files/bounce_msg-source.eml';

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
        $source   = __DIR__ . '/__files/html-source.eml';

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
        $source   = __DIR__ . '/__files/content_attachment-source.eml';

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        AssertContentAttachmentEmail::assertEquals($mail);
    }

    /**
     * Tests for an issue (#10) where the Factory was incorrectly handling emails with 9 child parts, as it would
     * incorrectly try to parse a 10th part (e.g. "1.10") because of non-strict checking for the value "1.10" in the
     * structure array containing a value "1.1"
     */
    public function testNineChildParts()
    {
        $source   = __DIR__ . '/__files/mime-9-parts-source.eml';

        $factory = new Factory();

        $mail = $factory->createFromFile($source);

        /** @var AbstractMime $mainPart */
        $mainPart = $mail->getPart();

        $this->assertEquals(9, count($mainPart->getParts()));
    }

    public function testGetPartFail()
    {
        $warningMsg = 'Couldn\'t get the part';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($warningMsg);

        $mailparse_msg_get_part = $this->getFunctionMock('\Phlib\Mail', 'mailparse_msg_get_part');
        $mailparse_msg_get_part->expects($this->once())
            ->willReturnCallback(function () use ($warningMsg) {
                trigger_error($warningMsg, E_USER_WARNING);
                return false;
            });

        $source   = __DIR__ . '/__files/bounce_msg-source.eml';

        (new Factory())->createFromFile($source);
    }

    public function testDecodeHeaderUtf8Base64()
    {
        $factory = new Factory();

        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?=' . "\r\n"
            . ' =?UTF-8?B?dWl0eSBQbGFuIC0gwqMxMDAgZGlzY291bnQgdG9kYXkgb25seSE=?=';

        $expected = [
            'charset' => 'UTF-8',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    public function testDecodeHeaderIsoQ()
    {
        $factory = new Factory();

        $header = '=?ISO-8859-1?Q?London Olympics: Business Continuity Plan - =A3100 discount today only!?=';

        $expected = [
            'charset' => 'ISO-8859-1',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    public function testDecodeHeaderPart()
    {
        $factory = new Factory();

        $header = 'London Olympics: Business Continuity Plan - =?ISO-8859-1?Q?=A3100?= discount today only!';

        $expected = [
            'charset' => 'ISO-8859-1',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    public function testDecodeHeaderMixed()
    {
        $factory = new Factory();

        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?='
            . ' =?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?=';

        $expected = [
            'charset' => 'UTF-8',
            'text' => 'London Olympics: Business ContinKeld Jørn Simonsen'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    public function testDecodeBrokenHeader()
    {
        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?=' . "\r\n"
            . ' =?UTF-8?B?dWl0eSBQbGFuIC0gwqPhlibDAgZGlzY291bnQgdG9kYXkgb25seSE=?=';
        $decoded = (new Factory())->decodeHeader($header);
        $this->assertEquals('UTF-8', $decoded['charset']);
        // test that we can at least show something if the encoded word is malformed (RFC 2047 section 6.3)
        $this->assertStringStartsWith('London Olympics: Business Contin', $decoded['text']);

    }

    public function testParseEmailAddresses()
    {
        $factory = new Factory();

        $addresses = 'recipient1@example.com, "Recipient Two" <recipient2@example.com>';

        $expected = [
            0 => [
                'display'  => 'recipient1@example.com',
                'address'  => 'recipient1@example.com',
                'is_group' => false,
            ],
            1 => [
                'display'  => 'Recipient Two',
                'address'  => 'recipient2@example.com',
                'is_group' => false,
            ]
        ];

        $this->assertEquals($expected, $factory->parseEmailAddresses($addresses));
    }
}
