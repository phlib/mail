<?php
declare(strict_types=1);

namespace Phlib\Mail\Tests;

use Phlib\Mail\AbstractPart;
use Phlib\Mail\Content\AbstractContent;
use Phlib\Mail\Content\Attachment;
use Phlib\Mail\Content\Content;
use Phlib\Mail\Content\Html;
use Phlib\Mail\Content\Text;
use Phlib\Mail\Mail;
use Phlib\Mail\Mime\AbstractMime;
use Phlib\Mail\Mime\MultipartAlternative;
use Phlib\Mail\Mime\MultipartMixed;
use Phlib\Mail\Mime\MultipartRelated;
use PHPUnit\Framework\Assert;

class AssertAttachmentsEmail
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
        $expectedHeaders = __DIR__ . '/__files/attachments-expected-headers.txt';
        Assert::assertEquals(file_get_contents($expectedHeaders), $mail->getEncodedHeaders());

        // Check parts are constructed as expected
        /** @var MultipartMixed $primaryPart */
        $primaryPart = $mail->getPart();
        Assert::assertInstanceOf(MultipartMixed::class, $primaryPart);

        /** @var AbstractMime[] $mixedParts */
        $mixedParts = $primaryPart->getParts();
        Assert::assertCount(5, $mixedParts);
        Assert::assertInstanceOf(MultipartRelated::class, $mixedParts[0]);
        Assert::assertInstanceOf(Attachment::class, $mixedParts[1]);
        Assert::assertInstanceOf(Attachment::class, $mixedParts[2]);
        Assert::assertInstanceOf(Attachment::class, $mixedParts[3]);
        Assert::assertInstanceOf(Attachment::class, $mixedParts[4]);

        /** @var AbstractPart[] $relatedParts */
        $relatedParts = $mixedParts[0]->getParts();
        Assert::assertCount(2, $relatedParts);
        Assert::assertInstanceOf(MultipartAlternative::class, $relatedParts[0]);
        Assert::assertInstanceOf(Attachment::class, $relatedParts[1]);

        /** @var AbstractContent[] $alternateParts */
        $alternateParts = $relatedParts[0]->getParts();
        Assert::assertCount(2, $alternateParts);
        Assert::assertInstanceOf(Text::class, $alternateParts[0]);
        Assert::assertInstanceOf(Html::class, $alternateParts[1]);

        // Check part content
        $content = [
            'text' => array(
                'part' => $alternateParts[0]
            ),
            'html' => array(
                'part' => $alternateParts[1]
            ),
            'attch1' => array(
                'part' => $relatedParts[1],
                'disposition' => false,
                'name' => '330.gif',
                'charset' => false,
                'type' => 'image/gif'
            ),
            'attch2' => array(
                'part' => $mixedParts[1],
                'disposition' => true,
                'name' => 'protocol.txt',
                'charset' => 'US-ASCII',
                'type' => 'text/plain'
            ),
            'attch3' => array(
                'part' => $mixedParts[2],
                'disposition' => true,
                'name' => 'example-logo.png',
                'charset' => false,
                'type' => 'image/png'
            ),
            'attch4' => array(
                'part' => $mixedParts[3],
                'disposition' => true,
                'name' => 'Tech_specs-letter_Crucial_m4_ssd_v3-11-11_online.pdf',
                'charset' => false,
                'type' => 'application/pdf'
            ),
            'attch5' => array(
                'part' => $mixedParts[4],
                'disposition' => true,
                'name' => 'plain.eml',
                'charset' => 'US-ASCII',
                'type' => 'text/plain'
            )
        ];

        foreach ($content as $name => $details) {
            /** @var AbstractContent $part */
            $part = $details['part'];

            // Test part content
            $expectedContent = __DIR__ . "/__files/attachments-expected-{$name}.txt";
            $expected = file_get_contents($expectedContent);
            $actual = $part->encodeContent($part->getContent());
            Assert::assertEquals($expected, $actual, $name);

            // Test attachments
            if ($part instanceof Attachment || $part instanceof Content) {
                $partHeaders = $part->getEncodedHeaders();
                $contentType = "Content-Type: {$details['type']};";
                if ($details['charset']) {
                    $contentType .= " charset=\"{$details['charset']}\";";
                }
                $contentType .= " name=\"{$details['name']}\"";
                Assert::assertContains($contentType, $partHeaders);
                if ($details['disposition'] === true) {
                    Assert::assertContains(
                        'Content-Disposition: attachment; filename="' . $details['name'] . '"',
                        $partHeaders
                    );
                } else {
                    Assert::assertNotContains('Content-Disposition', $partHeaders);
                }
            }
        }
    }
}
