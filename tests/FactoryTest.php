<?php

namespace Phlib\Mail\Tests;

use Phlib\Mail\Exception\RuntimeException;
use Phlib\Mail\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
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
     * Expect an exception, and there should be NO resource to free
     */
    public function testCreateFromFileNotFound()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be found');

        $source = __DIR__ . '/__files/does-not-exist';

        $factory = new Factory();
        $factory->createFromFile($source);
    }
}
