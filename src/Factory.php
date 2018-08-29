<?php

namespace Phlib\Mail;

use Phlib\Mail\Exception\RuntimeException;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;

class Factory
{
    /**
     * @var MailMimeParser
     */
    private $mailParser;

    public function __construct(MailMimeParser $mailParser = null)
    {
        if (!isset($mailParser)) {
            $mailParser = new MailMimeParser();
        }
        $this->mailParser = $mailParser;
    }

    /**
     * Load email from file
     *
     * @param string $filename path to file
     * @return Mail
     * @throws RuntimeException
     */
    public function createFromFile($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("Filename '{$filename}' cannot be found");
        }
        $handle = fopen($filename, 'r');
        $message = $this->mailParser->parse($handle);
        fclose($handle);

        return $this->parseEmail($message);
    }

    /**
     * @deprecated 2.1.0:3.0.0 Use createFromFile() to avoid statics and allow for the Factory to be used in DI
     * @param string $filename
     * @return Mail
     */
    public static function fromFile($filename)
    {
        return (new self)->createFromFile($filename);
    }

    /**
     * Load email from string
     *
     * @param string $source email as string
     * @return Mail
     * @throws RuntimeException
     */
    public function createFromString($source)
    {
        $message = $this->mailParser->parse($source);

        return $this->parseEmail($message);
    }

    /**
     * @deprecated 2.1.0:3.0.0 Use createFromString() to avoid statics and allow for the Factory to be used in DI
     * @param string $source
     * @return Mail
     */
    public static function fromString($source)
    {
        return (new self)->createFromString($source);
    }

    /**
     * Parse the email
     *
     * @param Message $message
     * @return Mail
     * @throws RuntimeException
     */
    private function parseEmail(Message $message)
    {
        $mail = new Mail();

        // Headers and meta info
        $this->addMailHeaders($mail, $message);

        // Parse from main part
        $child = $this->parsePart($message, $mail);
        $mail->setPart($child);

        return $mail;
    }

    /**
     * Add headers to the Mail object
     *
     * @param Mail $mail
     * @param Message $message
     * @return void
     */
    private function addMailHeaders(Mail $mail, Message $message)
    {
        /** @var AddressHeader $fromHeader */
        if (($fromHeader = $message->getHeader('from')) !== null) {
            try {
                $addresses = $fromHeader->getAddresses();
                if (!empty($addresses)) {
                    $mail->setFrom($addresses[0]->getEmail(), $addresses[0]->getName());
                }
            } catch (\InvalidArgumentException $e) {}
        }
        /** @var AddressHeader $replyToHeader */
        if (($replyToHeader = $message->getHeader('reply-to')) !== null) {
            try {
                $addresses = $replyToHeader->getAddresses();
                if (!empty($addresses)) {
                    $mail->setReplyTo($addresses[0]->getEmail(), $addresses[0]->getName());
                }

            } catch (\InvalidArgumentException $e) {}
        }
        if (($returnPath = $message->getHeaderValue('return-path', false)) !== false) {
            try {
                $mail->setReturnPath($returnPath);
            } catch (\InvalidArgumentException $e) {}
        }


        /** @var AddressHeader $header */
        foreach ($message->getAllHeadersByName('to') as $header) {
            foreach ($header->getAddresses() as $address) {
                try {
                    $mail->addTo($address->getEmail(), $address->getName());
                } catch (\InvalidArgumentException $e) {}
            }
        }
        /** @var AddressHeader $header */
        foreach ($message->getAllHeadersByName('cc') as $header) {
            foreach ($header->getAddresses() as $address) {
                try {
                    $mail->addCc($address->getEmail(), $address->getName());
                } catch (\InvalidArgumentException $e) {}
            }
        }

        if (($subject = $message->getHeaderValue('subject', false)) !== false) {
            try {
                $mail->setSubject($subject);
            } catch (\InvalidArgumentException $e) {}
        }

        $this->addHeaders($mail, $message, ['from', 'reply-to', 'return-path', 'cc', 'to', 'subject']);
    }

    /**
     * Add headers to a mail part
     *
     * @param AbstractPart $part
     * @param Message\Part\ParentHeaderPart $messagePart
     * @param array $excludeHeaders
     * @return void
     */
    private function addHeaders(AbstractPart $part, Message\Part\ParentHeaderPart $messagePart, array $excludeHeaders = [])
    {
        $headerKeys = array_keys(array_reduce($messagePart->getRawHeaders(), function($headerKeys, $header) use ($excludeHeaders) {
            list($headerKey) = $header;
            if (!in_array($headerKey, $excludeHeaders)) {
                $headerKeys[strtolower($headerKey)] = true;
            }
            return $headerKeys;
        }, []));

        // Iterate headers
        foreach ($headerKeys as $headerKey) {
            $headers = $messagePart->getAllHeadersByName($headerKey);
            foreach ($headers as $header) {
                $headerText = $header->getRaWValue();
                $headerText = preg_replace("/(\n|\r)\t/", ' ', $headerText);
                try {
                    $part->addHeader($headerKey, $headerText);
                } catch (\InvalidArgumentException $e) {}
            }
        }
    }

    /**
     * Recursively parse structure parts starting from the specified part
     *
     * @param Message\Part\MessagePart $part
     * @param Mail $mail
     * @return AbstractPart
     */
    private function parsePart(Message\Part\MessagePart $part, Mail $mail)
    {
        // Create correct Mail part object
        $type = $part->getContentType();

        if ($part instanceof Message\Part\MimePart && $part->isMultiPart()) {
            switch ($type) {
                case 'multipart/alternative':
                    $mailPart = new Mime\MultipartAlternative();
                    break;
                case 'multipart/mixed':
                    $mailPart = new Mime\MultipartMixed;
                    break;
                case 'multipart/related':
                    $mailPart = new Mime\MultipartRelated();
                    break;
                case 'multipart/report':
                    $mailPart = new Mime\MultipartReport();
                    if (($reportType = $part->getHeaderParameter('Content-Type', 'Report-Type', false)) !== false) {
                        $mailPart->setReportType($reportType);
                    }
                    break;
                default:
                    $mailPart = new Mime\Mime($type);
                    break;
            }

            foreach ($part->getChildParts() as $childPart) {
                $mailPart->addPart($this->parsePart($childPart, $mail));
            }
        } else {
            // Must be some sort of content, can't be an attachment for the primary part
            if (!($part instanceof  Message) &&
                $part instanceof Message\Part\MimePart &&
                ($contentName = $part->getHeaderParameter('Content-Type', 'name', false)) !== false
            ) {
                // It's an attachment
                $mail->incrementAttachmentCount();
                $disposition = $part->getContentDisposition(false);
                $mailPart = new Content\Attachment($contentName, $disposition, $type);
                $mailPart->setCharset($part->getCharset());
            } else {
                // Basic content
                switch ($type) {
                    case 'text/html':
                        $mailPart = new Content\Html();
                        break;
                    case 'text/plain':
                        $mailPart = new Content\Text();
                        break;
                    default:
                        // It's not HTML or text, so we count it as an attachment
                        $mail->incrementAttachmentCount();
                        $mailPart = new Content\Content($type);
                        $mailPart->setEncoding($part->getContentTransferEncoding());
                        break;
                }
                $mailPart->setCharset($part->getCharset());
            }

            $content = $part->getContent();

            $mailPart->setContent($content);
        }

        // Add any extra headers if this isn't the primary part
        if ($part instanceof Message\Part\ParentHeaderPart && !($part instanceof Message)) {
            $this->addHeaders($mailPart, $part);
        }

        return $mailPart;
    }
}
