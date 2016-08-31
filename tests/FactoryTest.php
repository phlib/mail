<?php

namespace Phlib\Tests\Mail;

use Phlib\Mail\Content\Content;
use Phlib\Mail\Content\Html;
use Phlib\Mail\Content\Text;
use Phlib\Mail\Factory;
use Phlib\Mail\Mime\MultipartAlternative;
use Phlib\Mail\Mime\MultipartMixed;
use Phlib\Mail\Mime\MultipartRelated;
use Phlib\Mail\Mime\MultipartReport;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     */
    public function testCreateFromFileAttachments()
    {
        $source   = __DIR__ . '/__files/attachments_source.eml';

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        $this->assertAttachmentsEmailEquals($mail);
        $this->assertEquals(true, $mail->hasAttachment());
        $this->assertEquals(5, $mail->getAttachmentCount());
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     */
    public function testCreateFromStringAttachments()
    {
        $source   = __DIR__ . '/__files/attachments_source.eml';

        $factory = new Factory();
        $mail = $factory->createFromString(file_get_contents($source));

        $this->assertAttachmentsEmailEquals($mail);
        $this->assertEquals(true, $mail->hasAttachment());
        $this->assertEquals(5, $mail->getAttachmentCount());
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     */
    public function testCreateFromFileBounceHead()
    {
        $source   = __DIR__ . '/__files/bouncehead_source.eml';

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        $this->assertBounceHeadEmailEquals($mail);
        $this->assertEquals(true, $mail->hasAttachment());
        $this->assertEquals(2, $mail->getAttachmentCount());
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     */
    public function testCreateFromFileBounceMsg()
    {
        $source   = __DIR__ . '/__files/bouncemsg_source.eml';

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        $this->assertBounceMsgEmailEquals($mail);
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     */
    public function testCreateFromFileHtml()
    {
        $source   = __DIR__ . '/__files/html_source.eml';

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        $this->assertHtmlEmailEquals($mail);
    }

    /**
     * Prevent giving code coverage to the Mail classes
     * @covers \Phlib\Mail\Factory
     * @requires PHP 5.4.17
     * see http://bugs.php.net/64166 this change appears to have affected the expected output from
     * quoted_printable_encode between PHP versions
     */
    public function testCreateFromContentAttachment()
    {
        $source   = __DIR__ . '/__files/contentattachment_source.eml';

        $factory = new Factory();
        $mail = $factory->createFromFile($source);

        $this->assertContentAttachmentEmailEquals($mail);
    }

    public function testDecodeHeaderUtf8Base64()
    {
        $factory = new Factory();

        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?=' . "\r\n"
            . ' =?UTF-8?B?dWl0eSBQbGFuIC0gwqMxMDAgZGlzY291bnQgdG9kYXkgb25seSE=?=';

        $expected = [
            'toCharset' => 'UTF-8',
            'fromCharset' => 'UTF-8',
            'encoding' => 'B',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    public function testDecodeHeaderIsoQ()
    {
        $factory = new Factory();

        $header = '=?ISO-8859-1?Q?London Olympics: Business Continuity Plan - =C2=A3100 discount today only!?=';

        $expected = [
            'toCharset' => 'ISO-8859-1',
            'fromCharset' => 'ISO-8859-1',
            'encoding' => 'Q',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    public function testDecodeHeaderPart()
    {
        $factory = new Factory();

        $header = 'London Olympics: Business Continuity Plan - =?ISO-8859-1?Q?=C2=A3100?= discount today only!';

        $expected = [
            'toCharset' => 'ISO-8859-1',
            'fromCharset' => 'ISO-8859-1',
            'encoding' => 'Q',
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
            'toCharset' => 'UTF-8',
            'fromCharset' => 'UTF-8',
            'encoding' => 'B',
            'text' => 'London Olympics: Business ContinKeld Jørn Simonsen'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    /**
     * @expectedException \Phlib\Mail\Exception\InvalidArgumentException
     */
    public function testDecodeHeaderFailure()
    {
        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?=' . "\r\n"
            . ' =?UTF-8?B?dWl0eSBQbGFuIC0gwqPhlibDAgZGlzY291bnQgdG9kYXkgb25seSE=?=';
        (new Factory())->decodeHeader($header);
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

    protected function assertAttachmentsEmailEquals(\Phlib\Mail\Mail $mail)
    {
        // Check headers
        $expectedHeaders = __DIR__ . '/__files/attachments_expected_headers.txt';
        $this->assertEquals(file_get_contents($expectedHeaders), $mail->getEncodedHeaders());

        // Check parts are constructed as expected
        /** @var \Phlib\Mail\Mime\MultipartMixed $primaryPart */
        $primaryPart = $mail->getPart();
        $this->assertInstanceOf(MultipartMixed::class, $primaryPart);

        $mixedParts = $primaryPart->getParts();
        $this->assertCount(5, $mixedParts);
        $this->assertInstanceOf(MultipartRelated::class, $mixedParts[0]);
        $this->assertInstanceOf(Content::class, $mixedParts[1]);
        $this->assertInstanceOf(Content::class, $mixedParts[2]);
        $this->assertInstanceOf(Content::class, $mixedParts[3]);
        $this->assertInstanceOf(Content::class, $mixedParts[4]);

        $relatedParts = $mixedParts[0]->getParts();
        $this->assertCount(2, $relatedParts);
        $this->assertInstanceOf(MultipartAlternative::class, $relatedParts[0]);
        $this->assertInstanceOf(Content::class, $relatedParts[1]);

        $alternateParts = $relatedParts[0]->getParts();
        $this->assertCount(2, $alternateParts);
        $this->assertInstanceOf(Text::class, $alternateParts[0]);
        $this->assertInstanceOf(Html::class, $alternateParts[1]);

        // Check part content
        /** @var \Phlib\Mail\Content\AbstractContent[] $content */
        $content = [
            'text' => $alternateParts[0],
            'html' => $alternateParts[1],
            'attch1' => $relatedParts[1],
            'attch2' => $mixedParts[1],
            'attch3' => $mixedParts[2],
            'attch4' => $mixedParts[3],
            'attch5' => $mixedParts[4]
        ];

        foreach ($content as $name => $part) {
            $expectedContent = __DIR__ . "/__files/attachments_expected_$name.txt";
            $expected = file_get_contents($expectedContent);
            $actual = $part->encodeContent($part->getContent());
            $this->assertEquals($expected, $actual, $name);
        }
    }

    protected function assertBounceHeadEmailEquals(\Phlib\Mail\Mail $mail)
    {
        // Check headers
        $expectedHeaders = __DIR__ . '/__files/bouncehead_expected_headers.txt';
        $this->assertEquals(file_get_contents($expectedHeaders), $mail->getEncodedHeaders());

        // Check parts are constructed as expected
        /** @var \Phlib\Mail\Mime\Mime $primaryPart */
        $primaryPart = $mail->getPart();
        $this->assertInstanceOf(MultipartReport::class, $primaryPart);
        $this->assertEquals('multipart/report', $primaryPart->getType());
        $this->assertContains('; report-type=delivery-status', $primaryPart->getEncodedHeaders());

        $reportParts = $primaryPart->getParts();
        $this->assertCount(3, $reportParts);
        $this->assertInstanceOf(Text::class, $reportParts[0]);
        $this->assertInstanceOf(Content::class, $reportParts[1]);
        $this->assertEquals('message/delivery-status', $reportParts[1]->getType());
        $this->assertInstanceOf(Content::class, $reportParts[2]);
        $this->assertEquals('text/rfc822-headers', $reportParts[2]->getType());

        // Check part content
        /** @var \Phlib\Mail\Content\AbstractContent[] $content */
        $content = [
            'text' => $reportParts[0],
            'status' => $reportParts[1],
            'message' => $reportParts[2]
        ];

        foreach ($content as $name => $part) {
            $expectedContent = __DIR__ . "/__files/bouncehead_expected_$name.txt";
            $expected = file_get_contents($expectedContent);
            $actual = $part->encodeContent($part->getContent());
            $this->assertEquals($expected, $actual, $name);
        }
    }

    protected function assertBounceMsgEmailEquals(\Phlib\Mail\Mail $mail)
    {
        // Check headers
        $expectedHeaders = __DIR__ . '/__files/bouncemsg_expected_headers.txt';
        $this->assertEquals(file_get_contents($expectedHeaders), $mail->getEncodedHeaders());

        // Check parts are constructed as expected
        /** @var \Phlib\Mail\Mime\Mime $primaryPart */
        $primaryPart = $mail->getPart();
        $this->assertInstanceOf(MultipartReport::class, $primaryPart);
        $this->assertEquals('multipart/report', $primaryPart->getType());
        $this->assertContains('; report-type=delivery-status', $primaryPart->getEncodedHeaders());

        $reportParts = $primaryPart->getParts();
        $this->assertCount(3, $reportParts);

        $this->assertInstanceOf(MultipartAlternative::class, $reportParts[0]);
        $alternateParts = $reportParts[0]->getParts();
        $this->assertCount(2, $alternateParts);
        $this->assertInstanceOf(Text::class, $alternateParts[0]);
        $this->assertInstanceOf(Html::class, $alternateParts[1]);

        $this->assertInstanceOf(Content::class, $reportParts[1]);
        $this->assertEquals('message/delivery-status', $reportParts[1]->getType());
        $this->assertInstanceOf(Content::class, $reportParts[2]);
        $this->assertEquals('message/rfc822', $reportParts[2]->getType());

        // Check part content
        /** @var \Phlib\Mail\Content\AbstractContent[] $content */
        $content = [
            'text' => $alternateParts[0],
            'html' => $alternateParts[1],
            'status' => $reportParts[1],
            'message' => $reportParts[2]
        ];

        foreach ($content as $name => $part) {
            $expectedContent = __DIR__ . "/__files/bouncemsg_expected_$name.txt";
            $expected = file_get_contents($expectedContent);
            $actual = $part->encodeContent($part->getContent());
            $this->assertEquals($expected, $actual, $name);
        }
    }

    protected function assertHtmlEmailEquals(\Phlib\Mail\Mail $mail)
    {
        // Check headers
        $expectedHeaders = __DIR__ . '/__files/html_expected_headers.txt';
        $this->assertEquals(file_get_contents($expectedHeaders), $mail->getEncodedHeaders());

        // Check parts are constructed as expected
        /** @var \Phlib\Mail\Mime\MultipartMixed $primaryPart */
        $primaryPart = $mail->getPart();
        $this->assertInstanceOf(Html::class, $primaryPart);

        // Check content
        $expectedContent = __DIR__ . "/__files/html_expected_html.txt";
        $expected = file_get_contents($expectedContent);
        $actual = $primaryPart->encodeContent($primaryPart->getContent());
        $this->assertEquals($expected, $actual);
    }

    protected function assertContentAttachmentEmailEquals(\Phlib\Mail\Mail $mail)
    {
        // Check headers
        $expectedHeaders = __DIR__ . '/__files/contentattachment_expected_headers.txt';
        $this->assertEquals(file_get_contents($expectedHeaders), $mail->getEncodedHeaders());

        // Check parts are constructed as expected
        /** @var \Phlib\Mail\Content\Html $primaryPart */
        $primaryPart = $mail->getPart();
        $this->assertInstanceOf(Html::class, $primaryPart);

        // Check content
        $expectedContent = __DIR__ . "/__files/contentattachment_expected_html.txt";
        $expected = file_get_contents($expectedContent);
        $actual = $primaryPart->encodeContent($primaryPart->getContent());
        $this->assertEquals($expected, $actual);
    }
}
