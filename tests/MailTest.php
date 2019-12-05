<?php

declare(strict_types=1);

namespace Phlib\Mail\Tests;

use Phlib\Mail\Content\Content;
use Phlib\Mail\Exception\InvalidArgumentException;
use Phlib\Mail\Exception\RuntimeException;
use Phlib\Mail\Mail;
use Phlib\Mail\Mime\Mime;
use PHPUnit\Framework\TestCase;

class MailTest extends TestCase
{
    /**
     * @var \Phlib\Mail\Mail
     */
    protected $mail;

    protected function setUp(): void
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

    public function testGetPartNotSet()
    {
        $this->expectException(RuntimeException::class);
        $this->mail->getPart();
    }

    /**
     * @covers \Phlib\Mail\Mail
     * @uses \Phlib\Mail\Content\Content<extended>
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
     * @uses \Phlib\Mail\Mime\Mime<extended>
     */
    public function testGetEncodedHeadersWithData()
    {
        $part = new Mime('multipart/other');
        $this->mail->setPart($part);

        $expected = $this->addHeaders();
        $expected['MIME-Version'] = '1.0';

        $this->assertEquals($expected, iconv_mime_decode_headers($this->mail->getEncodedHeaders()));
    }

    public function testGetEncodedHeadersNotSet()
    {
        $this->expectException(RuntimeException::class);

        $expected = "";
        $this->assertEquals($expected, $this->mail->getEncodedHeaders());
    }

    /**
     * @dataProvider providerEncodedHeadersAddrSpec
     */
    public function testEncodedHeadersMailboxAddrSpec(
        string $method,
        array $params,
        string $expected
    ): void {
        $part = new Mime('multipart/other');
        $this->mail->setPart($part);

        foreach ($params as $each) {
            $this->mail->{$method}(...$each);
        }

        $expected .= "MIME-Version: 1.0";

        // Remove line-breaks for character comparison
        $actual = str_replace("\r\n", '', $this->mail->getEncodedHeaders());

        $this->assertEquals($expected, $actual);
    }

    /**
     * Annotation for IDE usage
     *
     * @see Mail::setFrom()
     * @see Mail::setReplyTo()
     * @see Mail::setReturnPath()
     * @see Mail::addTo()
     * @see Mail::addCc()
     */
    public function providerEncodedHeadersAddrSpec(): iterable
    {
        $singleHeaders = [
            'From' => 'setFrom',
            'Reply-To' => 'setReplyTo',
        ];
        $listHeaders = [
            'To' => 'addTo',
            'Cc' => 'addCc',
        ];

        /**
         * @var array $names
         *     string? Display Name parameter
         *     string? Encoded Value (null for no Display Name)
         */
        $names = [
            'No name' => [null, null],
            'Empty name' => ['', null],
            'One atom' => ['Atom0123=?*&789', 'Atom0123=?*&789'],
            'Atom phrase' => ['Atom01 23=?*&789', 'Atom01 23=?*&789'],
            'One extended' => ['Exténded', '=?UTF-8?Q?Ext=C3=A9nded?='],
            'One extended in phrase' => ['Contains Exténded Word', 'Contains =?UTF-8?Q?Ext=C3=A9nded?= Word'],
            'Extended whole phrase' => ['Âll Exténded Wörds', '=?UTF-8?Q?=C3=82ll_Ext=C3=A9nded_W=C3=B6rds?='],
        ];

        // For all headers,
        foreach (array_merge($singleHeaders, $listHeaders) as $header => $method) {
            foreach ($names as $providerName => $config) {
                [$name, $expectedDisplay] = $config;
                $address = uniqid() . '@example.com';
                $expected = $header . ': ';
                if ($expectedDisplay === null) {
                    $expected .= $address;
                } else {
                    $expected .= "{$expectedDisplay} <$address>";
                }
                $params = [[$address, $name]];
                yield "Single {$header} {$providerName}" => [$method, $params, $expected];
            }
        }

        // For List headers, also build examples with multiple addresses
        foreach ($listHeaders as $header => $method) {
            foreach ($names as $providerName => $config) {
                [$name, $expectedDisplay] = $config;
                $expectedParts = [];
                $params = [];
                for ($i = 0; $i < 3; $i++) {
                    $address = uniqid() . '@example.com';
                    if ($expectedDisplay === null) {
                        $expectedParts[] = $address;
                    } else {
                        $expectedParts[] = "{$expectedDisplay} <$address>";
                    }
                    $params[] = [$address, $name];
                }
                $expected = $header . ': ' . implode(', ', $expectedParts);
                yield "Multi {$header} {$providerName}" => [$method, $params, $expected];
            }
        }
    }

    public function testSetGetReturnPath()
    {
        $address = 'return-path@example.com';
        $this->mail->setReturnPath($address);

        $this->assertEquals($address, $this->mail->getReturnPath());
    }

    public function testSetReturnPathInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
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

    public function testGetReturnPathDefault(): void
    {
        $this->assertSame(null, $this->mail->getReturnPath());
    }

    public function testAddGetReceived()
    {
        $data = [
            [
                'from localhost (localhost [127.0.0.1]) by mail.example.com (Postfix) for <recipient@example.com>',
                'Thu, 16 Aug 2012 15:45:43 +0100'
            ],
            [
                'from gbnthda3150srv.example.com ([10.67.121.52]) by GBLONVMSX001.nsicorp.int',
                'Thu, 29 Sep 2011 08:48:51 +0100'
            ],
        ];
        $expected = [];
        foreach ($data as $received) {
            $this->mail->addReceived($received[0], new \DateTimeImmutable($received[1]));
            $expected[] = implode('; ', $received);
        }

        $this->assertEquals($expected, $this->mail->getReceived());
    }

    public function testGetReceivedDefault(): void
    {
        $this->assertSame([], $this->mail->getReceived());
    }

    public function testSetGetOriginationDate()
    {
        $date = new \DateTimeImmutable('Mon, 20 Aug 2012 05:15:26 +0100');
        $this->mail->setOriginationDate($date);

        $this->assertEquals($date, $this->mail->getOriginationDate());
    }

    public function testGetOriginationDateDefault(): void
    {
        $this->assertSame(null, $this->mail->getOriginationDate());
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

    public function testSetFromInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mail->setFrom('invalid address');
    }

    public function testGetFromDefault(): void
    {
        $this->assertSame(null, $this->mail->getFrom());
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

    public function testSetReplyToInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mail->setReplyTo('invalid address');
    }

    public function testGetReplyToDefault(): void
    {
        $this->assertSame(null, $this->mail->getReplyTo());
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

    public function testAddToInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mail->addTo('invalid address');
    }

    public function testClearTo()
    {
        $this->mail->addTo('to@example.com');
        $expectedBefore = ['to@example.com' => null];
        $this->assertEquals($expectedBefore, $this->mail->getTo());

        $this->mail->clearTo();
        $expectedAfter = [];
        $this->assertEquals($expectedAfter, $this->mail->getTo());
    }

    public function testGetToDefault(): void
    {
        $this->assertSame([], $this->mail->getTo());
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

    public function testAddCcInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mail->addCc('invalid address');
    }

    public function testClearCc()
    {
        $this->mail->addCc('cc@example.com');
        $expectedBefore = ['cc@example.com' => null];
        $this->assertEquals($expectedBefore, $this->mail->getCc());

        $this->mail->clearCc();
        $expectedAfter = [];
        $this->assertEquals($expectedAfter, $this->mail->getCc());
    }

    public function testGetCcDefault(): void
    {
        $this->assertSame([], $this->mail->getCc());
    }

    public function testSetGetMessageId()
    {
        $messageId = 'abc123@example.com';
        $this->mail->setMessageId($messageId);

        $this->assertEquals($messageId, $this->mail->getMessageId());
    }

    public function testSetMessageIdInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mail->setMessageId('invalid address');
    }

    /**
     * Message-Id header must never be encoded
     * When using mb_encode_mimeheader() an underscore triggered it to encode the string even though unnecessary
     * Even when longer than soft limit of 78 chars, we don't want Message-Id to be wrapped
     */
    public function testSetMessageIdNotEncoded()
    {
        $part = new Mime('multipart/other');
        $this->mail->setPart($part);

        $messageId = '5ba50e335feeb_58fbb46426474f8d8108b1f8e02bad29@mail.long.example.com';
        $this->mail->setMessageId($messageId);

        $expected = "Message-Id: <{$messageId}>\r\n" .
            "MIME-Version: 1.0\r\n";

        $this->assertEquals($expected, $this->mail->getEncodedHeaders());
    }

    public function testGetMessageIdDefault(): void
    {
        $this->assertSame(null, $this->mail->getMessageId());
    }

    public function testSetGetInReplyToSingle()
    {
        $messageId = 'abc123@example.com';
        $this->mail->setInReplyTo([$messageId]);

        $this->assertEquals([$messageId], $this->mail->getInReplyTo());
    }

    public function testSetGetInReplyToMultiple()
    {
        $messageIds = [
            'abc123@example.com',
            'def456@example.com',
        ];
        $this->mail->setInReplyTo($messageIds);

        $this->assertEquals($messageIds, $this->mail->getInReplyTo());
    }

    public function testSetInReplyToInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mail->setInReplyTo(['invalid address']);
    }

    public function testGetInReplyToDefault(): void
    {
        $this->assertSame(null, $this->mail->getInReplyTo());
    }

    public function testSetGetReferencesSingle()
    {
        $messageId = 'abc123@example.com';
        $this->mail->setReferences([$messageId]);

        $this->assertEquals([$messageId], $this->mail->getReferences());
    }

    public function testSetGetReferencesMultiple()
    {
        $messageIds = [
            'abc123@example.com',
            'def456@example.com',
        ];
        $this->mail->setReferences($messageIds);

        $this->assertEquals($messageIds, $this->mail->getReferences());
    }

    public function testSetReferencesInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mail->setReferences(['invalid address']);
    }

    public function testGetReferencesDefault(): void
    {
        $this->assertSame(null, $this->mail->getReferences());
    }

    public function testSetGetSubject()
    {
        $subject = 'subject line';
        $this->mail->setSubject($subject);

        $this->assertEquals($subject, $this->mail->getSubject());
    }

    public function testGetSubjectDefault(): void
    {
        $this->assertSame(null, $this->mail->getSubject());
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
     * @uses \Phlib\Mail\Content\Content<extended>
     */
    public function testToString()
    {
        $expectedHeaders = $this->addHeaders();
        $expectedHeaders['Content-Type'] = 'application/octet-stream; charset=UTF-8';
        $expectedHeaders['Content-Transfer-Encoding'] = 'quoted-printable';

        $content = 'test content';
        $part = new Content();
        $part->setContent($content);
        $part->setCharset('UTF-8');
        $this->mail->setPart($part);

        $actual = $this->mail->toString();
        list($actualHeaders, $actualContent) = explode("\r\n\r\n", $actual, 2);

        $this->assertEquals($expectedHeaders, iconv_mime_decode_headers($actualHeaders));
        $this->assertEquals($content, trim($actualContent));
    }

    /**
     * Add headers to the mail object and return the expected header string
     *
     * @return array
     */
    protected function addHeaders()
    {
        $originationDate = new \DateTimeImmutable('2014-04-23 00:12:32 +0100');
        $receivedDate1 = new \DateTimeImmutable('2014-04-22 23:12:45 +0000');
        $receivedDate2 = new \DateTimeImmutable('2014-04-23 06:13:12 -0700');
        $this->mail->setReturnPath('return-path@example.com');
        $this->mail->addReceived('from localhost by mail1.example.com', $receivedDate1);
        $this->mail->addReceived('from mail1.example.com by mail2.example.com', $receivedDate2);
        $this->mail->setOriginationDate($originationDate);
        $this->mail->setFrom('from@example.com', "From Alias \xf0\x9f\x93\xa7 envelope");
        $this->mail->setReplyTo('reply-to@example.com');
        $this->mail->addTo('to+1@example.com', "To Alias 1 \xf0\x9f\x93\xa7 envelope");
        $this->mail->addTo('to+2@example.com', "To Alias 2 \xf0\x9f\x93\xa7 envelope");
        $this->mail->addCc('cc@example.com');
        $this->mail->setMessageId('abc.123.def@mail.example.com');
        $this->mail->setInReplyTo(['abc.123@mail.example.com', 'def.456@mail.example.com']);
        $this->mail->setReferences(['fed.098@mail.example.com', 'cba.765@mail.example.com']);
        $this->mail->setSubject("subject line with \xf0\x9f\x93\xa7 envelope");

        $expected = [
            'Return-Path' => '<return-path@example.com>',
            'Received' => [
                'from localhost by mail1.example.com; ' . $receivedDate1->format(\DateTime::RFC2822),
                'from mail1.example.com by mail2.example.com; ' . $receivedDate2->format(\DateTime::RFC2822),
            ],
            'Date' => $originationDate->format(\DateTime::RFC2822),
            'From' => "From Alias \xf0\x9f\x93\xa7 envelope <from@example.com>",
            'Reply-To' => 'reply-to@example.com',
            'To' => "To Alias 1 \xf0\x9f\x93\xa7 envelope <to+1@example.com>," .
                " To Alias 2 \xf0\x9f\x93\xa7 envelope <to+2@example.com>",
            'Cc' => 'cc@example.com',
            'Message-Id' => '<abc.123.def@mail.example.com>',
            'In-Reply-To' => '<abc.123@mail.example.com> <def.456@mail.example.com>',
            'References' => '<fed.098@mail.example.com> <cba.765@mail.example.com>',
            'Subject' => "subject line with \xf0\x9f\x93\xa7 envelope",
        ];

        return $expected;
    }
}
