<?php

namespace Phlib\Tests\Mail;

use Phlib\Mail\Content\Content;
use Phlib\Mail\Mail;
use Phlib\Mail\Mime\Mime;

class MailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Phlib\Mail\Mail
     */
    protected $mail;

    protected function setUp()
    {
        $this->mail = new Mail();
    }

    public function testSetGetCharset()
    {
        $charset = 'utf8';
        $this->mail->setCharset($charset);

        $this->assertEquals($charset, $this->mail->getCharset());
    }

    public function testSetGetPart()
    {
        $part = new Content();
        $this->mail->setPart($part);

        $this->assertEquals($part, $this->mail->getPart());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetPartNotSet()
    {
        $this->mail->getPart();
    }

    /**
     * @covers \Phlib\Mail\Mail
     */
    public function testGetEncodedHeaders()
    {
        $part = new Content();
        $this->mail->setPart($part);

        $expected = "";
        $this->assertEquals($expected, $this->mail->getEncodedHeaders());
    }

    /**
     * @covers \Phlib\Mail\Mail
     */
    public function testGetEncodedHeadersWithData()
    {
        $part = new Mime();
        $this->mail->setPart($part);

        $expected = $this->addHeaders();
        $expected .= "MIME-Version: 1.0\r\n";

        $this->assertEquals($expected, $this->mail->getEncodedHeaders());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetEncodedHeadersNotSet()
    {
        $expected = "";
        $this->assertEquals($expected, $this->mail->getEncodedHeaders());
    }

    public function testAddGetTo()
    {
        $data = [
            'to-1@example.com' => 'To Alias 1',
            'to-2@example.com' => null
        ];
        foreach ($data as $address => $name) {
            $this->mail->addTo($address, $name);
        }

        $this->assertEquals($data, $this->mail->getTo());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddToInvalid()
    {
        $this->mail->addTo('invalid address');
    }

    public function testClearTo()
    {
        $this->mail->addTo('to@example.com');
        $expectedBefore = ['to@example.com' => null];
        $this->assertEquals($expectedBefore, $this->mail->getTo());

        $this->mail->clearTo();
        $expectedAfter = array();
        $this->assertEquals($expectedAfter, $this->mail->getTo());
    }

    public function testAddGetCc()
    {
        $data = [
            'cc-1@example.com' => 'Cc Alias 1',
            'cc-2@example.com' => null
        ];
        foreach ($data as $address => $name) {
            $this->mail->addCc($address, $name);
        }

        $this->assertEquals($data, $this->mail->getCc());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddCcInvalid()
    {
        $this->mail->addCc('invalid address');
    }

    public function testClearCc()
    {
        $this->mail->addCc('cc@example.com');
        $expectedBefore = ['cc@example.com' => null];
        $this->assertEquals($expectedBefore, $this->mail->getCc());

        $this->mail->clearCc();
        $expectedAfter = array();
        $this->assertEquals($expectedAfter, $this->mail->getCc());
    }

    public function testSetGetReplyTo()
    {
        $data = [
            'reply-to@example.com',
            'Reply-To Alias'
        ];
        $this->mail->setReplyTo($data[0], $data[1]);

        $this->assertEquals($data, $this->mail->getReplyTo());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetReplyToInvalid()
    {
        $this->mail->setReplyTo('invalid address');
    }

    public function testSetGetReturnPath()
    {
        $address = 'return-path@example.com';
        $this->mail->setReturnPath($address);

        $this->assertEquals($address, $this->mail->getReturnPath());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetReturnPathInvalid()
    {
        $this->mail->setReturnPath('invalid address');
    }

    public function testClearReturnPath()
    {
        $address = 'return-path@example.com';
        $this->mail->setReturnPath($address);
        $this->assertEquals($address, $this->mail->getReturnPath());

        $this->mail->clearReturnPath();
        $this->assertEquals(null, $this->mail->getReturnPath());
    }

    public function testSetGetFrom()
    {
        $data = [
            'from@example.com',
            'From Alias'
        ];
        $this->mail->setFrom($data[0], $data[1]);

        $this->assertEquals($data, $this->mail->getFrom());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetFromInvalid()
    {
        $this->mail->setFrom('invalid address');
    }

    public function testFilterName()
    {
        $address = 'dummy@example.com';
        $name = "\r\n\t\"<>'[]";
        $expected = [
            $address => "'[]'[]"
        ];
        $this->mail->addTo($address, $name);

        $this->assertEquals($expected, $this->mail->getTo());
    }

    public function testSetGetSubject()
    {
        $subject = 'subject line';
        $this->mail->setSubject($subject);

        $this->assertEquals($subject, $this->mail->getSubject());
    }

    /**
     * @covers \Phlib\Mail\Mail
     */
    public function testFormatAddress()
    {
        $address = 'dummy@example.com';
        $name = 'Address Alias';
        $expected = "$name <$address>";
        $actual = $this->mail->formatAddress($address, $name);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \Phlib\Mail\Mail
     */
    public function testFormatAddressEscaped()
    {
        $address = 'dummy@example.com';
        $name = 'Address,Alias';
        $expected = "\"$name\" <$address>";
        $actual = $this->mail->formatAddress($address, $name);

        $this->assertEquals($expected, $actual);
    }

    public function testHasAttachmentFalse()
    {
        $this->assertEquals(false, $this->mail->hasAttachment());
    }

    public function testHasAttachmentTrue()
    {
        $this->mail->incrementAttachmentCount();
        $this->assertEquals(true, $this->mail->hasAttachment());
    }

    public function testIncrementAttachmentCount()
    {
        // 0
        $this->assertEquals(0, $this->mail->getAttachmentCount());

        // 1
        $this->mail->incrementAttachmentCount();
        $this->assertEquals(1, $this->mail->getAttachmentCount());

        // 2
        $this->mail->incrementAttachmentCount();
        $this->assertEquals(2, $this->mail->getAttachmentCount());
        $this->assertEquals(true, $this->mail->hasAttachment());

        // 1
        $this->mail->decrementAttachmentCount();
        $this->assertEquals(1, $this->mail->getAttachmentCount());

        // 0
        $this->mail->decrementAttachmentCount();
        $this->assertEquals(0, $this->mail->getAttachmentCount());
        $this->assertEquals(false, $this->mail->hasAttachment());
    }

    /**
     * @covers \Phlib\Mail\Mail
     */
    public function testToString()
    {
        $expectedHeaders = $this->addHeaders();

        $content = 'test content';
        $part = new Content();
        $part->setContent($content);
        $this->mail->setPart($part);
        $expectedContent =
            "Content-Type: application/octet-stream; charset=\"UTF-8\"\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n"
            . "\r\n"
            . $content;

        $expected = $expectedHeaders . $expectedContent;
        $this->assertEquals($expected, $this->mail->toString());
    }

    /**
     * Add headers to the mail object and return the expected header string
     *
     * @return string
     */
    protected function addHeaders()
    {
        $this->mail->setReturnPath('return-path@example.com');
        $this->mail->setFrom('from@example.com');
        $this->mail->setSubject('subject line');
        $this->mail->addTo('to@example.com');
        $this->mail->addCc('cc@example.com');
        $this->mail->setReplyTo('reply-to@example.com');

        $expected = "Return-Path: <return-path@example.com>\r\n"
            . "From: from@example.com\r\n"
            . "Subject: subject line\r\n"
            . "To: to@example.com\r\n"
            . "Cc: cc@example.com\r\n"
            . "Reply-To: reply-to@example.com\r\n";

        return $expected;
    }
}
