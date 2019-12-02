<?php

declare(strict_types=1);

namespace Phlib\Mail\Tests;

use Phlib\Mail\AbstractPart;
use Phlib\Mail\Content\AbstractContent;
use Phlib\Mail\Content\Html;
use Phlib\Mail\Content\Text;
use Phlib\Mail\Factory;
use Phlib\Mail\Mime\AbstractMime;
use Phlib\Mail\Mime\MultipartAlternative;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{

    public function testRecreateMultipartMail()
    {
        $originalMail = new \Phlib\Mail\Mail();
        $originalMail->addTo('to@example.com');
        $originalMail->setFrom('from@example.com');
        $originalMail->setSubject('Subject line');
        $contentPart = new MultipartAlternative();
        $contentPart->addPart((new Html())
            ->setContent('Test <b>html</b>')
            ->setEncoding(AbstractPart::ENCODING_BASE64));
        $contentPart->addPart((new Text())
            ->setContent('Test text')
            ->setEncoding(AbstractPart::ENCODING_BASE64));
        $originalMail->setPart($contentPart);

        $source = $originalMail->toString();

        $mail = (new Factory())->createFromString($source);

        /** @var AbstractMime $mainPart */
        $mainPart = $mail->getPart();
        $this->assertInstanceOf(AbstractMime::class, $mainPart);
        $parts    = $mainPart->getParts();
        $this->assertCount(2, $parts);
        /** @var AbstractContent $htmlPart */
        /** @var AbstractContent $textPart */
        list($htmlPart, $textPart) = $parts;

        $this->assertEquals('Test <b>html</b>', $htmlPart->getContent());
        $this->assertEquals('Test text', $textPart->getContent());
    }

    public function testRecreateContentOnlyMail()
    {
        $originalMail = new \Phlib\Mail\Mail();
        $originalMail->addTo('to@example.com');
        $originalMail->setFrom('from@example.com');
        $originalMail->setSubject('Subject line');
        $contentPart = (new Text())
            ->setContent('Test text')
            ->setEncoding(AbstractPart::ENCODING_BASE64);
        $originalMail->setPart($contentPart);

        $source = $originalMail->toString();

        $mail = (new Factory())->createFromString($source);

        /** @var AbstractContent $mainPart */
        $mainPart = $mail->getPart();
        $this->assertInstanceOf(AbstractContent::class, $mainPart);

        $this->assertEquals('Test text', $mainPart->getContent());
    }
}
