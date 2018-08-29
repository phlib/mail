<?php

namespace Phlib\Mail;

use Phlib\Mail\Exception\RuntimeException;
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
        $this->addMailHeaders($mail, $message->getRawHeaders());

        // Parse from main part
        $child = $this->parsePart($message, $mail);
        $mail->setPart($child);

        return $mail;
    }

    /**
     * Add headers to the Mail object
     *
     * @param Mail $mail
     * @param array $headers
     * @return void
     */
    private function addMailHeaders(Mail $mail, array $headers)
    {
        $charset = null;

        // Iterate headers
        foreach ($headers as $header) {
            list($headerKey, $headerEncoded) = $header;
            // Decode
            $headerDecoded = $this->decodeHeader($headerEncoded, $charset);
            if (is_null($charset) && !is_null($headerDecoded['charset'])) {
                // Set first discovered charset
                $charset = $headerDecoded['charset'];
                $mail->setCharset($charset);
            }
            $headerText = $headerDecoded['text'];

            try {
                switch (strtolower($headerKey)) {
                    case 'from':
                    case 'reply-to':
                    case 'return-path':
                        $addresses = $this->parseEmailAddresses($headerText);
                        $method = 'set' . str_replace(' ', '', ucwords(
                            str_replace('-', ' ', strtolower($headerKey))
                        ));
                        foreach ($addresses as $address) {
                            $mail->{$method}(
                                $address['address'],
                                ($address['display'] == $address['address']) ? null : $address['display']
                            );
                        }
                        break;
                    case 'cc':
                    case 'to':
                        $addresses = $this->parseEmailAddresses($headerText);
                        $method = 'add' . ucwords(strtolower($headerKey));
                        foreach ($addresses as $address) {
                            $mail->{$method}(
                                $address['address'],
                                ($address['display'] == $address['address']) ? null : $address['display']
                            );
                        }
                        break;
                    case 'subject':
                        $mail->setSubject($headerText);
                        break;
                    default:
                        $mail->addHeader($headerKey, $headerText);
                        break;
                }
            } catch (\InvalidArgumentException $e) {
            }
        }
    }

    /**
     * Add headers to a mail part
     *
     * @param AbstractPart $part
     * @param array $headers
     * @return void
     */
    private function addHeaders(AbstractPart $part, array $headers)
    {
        $charset = null;
        if (method_exists($part, 'getCharset')) {
            $charset = $part->getCharset();
        }

        // Iterate headers
        foreach ($headers as $header) {
            list($headerKey, $headerEncoded) = $header;
            $headerDecoded = $this->decodeHeader($headerEncoded, $charset);
            $headerText = $headerDecoded['text'];
            try {
                $part->addHeader($headerKey, $headerText);
            } catch (\InvalidArgumentException $e) {
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
            $this->addHeaders($mailPart, $part->getRawHeaders());
        }

        return $mailPart;
    }

    /**
     * Decode header
     *
     * @deprecated 2.1.0:3.0.0 Method should not have been available in the public interface
     * @param string $header Encoded header
     * @param string $charset Target charset. Optional. Default will use source charset where available.
     * @return array {
     *     @var string $text    Decoded header
     *     @var string $charset Charset of the decoded header
     * }
     * @throws InvalidArgumentException
     */
    public function decodeHeader($header, $charset = null)
    {
        $header = preg_replace("/(\n|\r)\t/", ' ', $header);

        if (preg_match('/=\?([^\?]+)\?([^\?])\?[^\?]+\?=/', $header, $matches) > 0) {
            if ($charset === null) {
                $charset = $matches[1];
            }

            // Workaround for https://bugs.php.net/bug.php?id=68821
            $header = preg_replace_callback('/(=\?[^\?]+\?Q\?)([^\?]+)(\?=)/i', function ($matches) {
                return $matches[1] . str_replace('_', '=20', $matches[2]) . $matches[3];
            }, $header);

            $header = mb_decode_mimeheader($header);
        }

        return [
            'charset' => $charset,
            'text' => $header
        ];
    }

    /**
     * Parse RFC 822 formatted email addresses string
     *
     * @deprecated 2.1.0:3.0.0 Method should not have been available in the public interface
     * @see mailparse_rfc822_parse_addresses()
     * @param string $addresses
     * @return array 'display', 'address' and 'is_group'
     * @see mailparse_rfc822_parse_addresses
     */
    public function parseEmailAddresses($addresses)
    {
        return mailparse_rfc822_parse_addresses($addresses);
    }
}
