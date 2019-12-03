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
        $expectedHeaders['Content-Type'] = 'application/octet-stream; charset="UTF-8"';
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
        $this->mail->setReturnPath('return-path@example.com');
        $this->mail->setFrom('from@example.com', "From Alias \xf0\x9f\x93\xa7 envelope");
        $this->mail->setReplyTo('reply-to@example.com');
        $this->mail->addTo('to+1@example.com', "To Alias 1 \xf0\x9f\x93\xa7 envelope");
        $this->mail->addTo('to+2@example.com', "To Alias 2 \xf0\x9f\x93\xa7 envelope");
        $this->mail->addCc('cc@example.com');
        $this->mail->setSubject("subject line with \xf0\x9f\x93\xa7 envelope");

        $expected = [
            'Return-Path' => '<return-path@example.com>',
            'From' => "From Alias \xf0\x9f\x93\xa7 envelope <from@example.com>",
            'Reply-To' => 'reply-to@example.com',
            'To' => "To Alias 1 \xf0\x9f\x93\xa7 envelope <to+1@example.com>," .
                " To Alias 2 \xf0\x9f\x93\xa7 envelope <to+2@example.com>",
            'Cc' => 'cc@example.com',
            'Subject' => "subject line with \xf0\x9f\x93\xa7 envelope",
        ];

        return $expected;
    }
}
