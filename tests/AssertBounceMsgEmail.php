<?php

declare(strict_types=1);

namespace Phlib\Mail\Tests;

use Phlib\Mail\Content\AbstractContent;
use Phlib\Mail\Content\Content;
use Phlib\Mail\Content\Html;
use Phlib\Mail\Content\Text;
use Phlib\Mail\Mail;
use Phlib\Mail\Mime\MultipartAlternative;
use Phlib\Mail\Mime\MultipartReport;
use PHPUnit\Framework\Assert;

class AssertBounceMsgEmail
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
        $expectedHeaders = __DIR__ . '/__files/bounce_msg-expected-headers.txt';
        Assert::assertEquals(file_get_contents($expectedHeaders), $mail->getEncodedHeaders());

        // Check parts are constructed as expected
        /** @var MultipartReport $primaryPart */
        $primaryPart = $mail->getPart();
        Assert::assertInstanceOf(MultipartReport::class, $primaryPart);
        Assert::assertEquals('multipart/report', $primaryPart->getType());
        Assert::assertStringContainsString('; report-type=delivery-status', $primaryPart->getEncodedHeaders());

        $reportParts = $primaryPart->getParts();
        Assert::assertCount(3, $reportParts);

        Assert::assertInstanceOf(MultipartAlternative::class, $reportParts[0]);
        $alternateParts = $reportParts[0]->getParts();
        Assert::assertCount(2, $alternateParts);
        Assert::assertInstanceOf(Text::class, $alternateParts[0]);
        Assert::assertInstanceOf(Html::class, $alternateParts[1]);

        Assert::assertInstanceOf(Content::class, $reportParts[1]);
        Assert::assertEquals('message/delivery-status', $reportParts[1]->getType());
        Assert::assertInstanceOf(Content::class, $reportParts[2]);
        Assert::assertEquals('message/rfc822', $reportParts[2]->getType());

        // Check part content
        /** @var AbstractContent[] $content */
        $content = [
            'text' => $alternateParts[0],
            'html' => $alternateParts[1],
            'status' => $reportParts[1],
            'message' => $reportParts[2]
        ];

        foreach ($content as $name => $part) {
            $expectedContent = __DIR__ . "/__files/bounce_msg-expected-{$name}.txt";
            $expected = file_get_contents($expectedContent);
            $actual = $part->encodeContent($part->getContent());
            Assert::assertEquals($expected, $actual, $name);
        }
    }
}
