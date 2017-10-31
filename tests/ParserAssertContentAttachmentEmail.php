<?php

namespace Phlib\Mail\Tests;

use Phlib\Mail\Content\Html;
use Phlib\Mail\Mail;
use PHPUnit\Framework\Assert;

class ParserAssertContentAttachmentEmail
{
    /**
     * Assert the Mail object has the expected parts
     *
     * @param Mail $mail
     * @return void
     */
    public static function assertEquals(Mail $mail)
    {
        // Check headers
        $expectedHeaders = __DIR__ . '/__files/content_attachment-expected-headers.txt';
        Assert::assertEquals(file_get_contents($expectedHeaders), $mail->getEncodedHeaders());

        // Check parts are constructed as expected
        /** @var Html $primaryPart */
        $primaryPart = $mail->getPart();
        Assert::assertInstanceOf(Html::class, $primaryPart);

        // Check content
        $expectedContent = __DIR__ . "/__files/content_attachment-expected-html.txt";
        $expected = file_get_contents($expectedContent);
        $actual = $primaryPart->encodeContent($primaryPart->getContent());
        Assert::assertEquals($expected, $actual);
    }
}
