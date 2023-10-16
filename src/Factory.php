<?php

declare(strict_types=1);

namespace Phlib\Mail;

use Phlib\Mail\Exception\RuntimeException;

class Factory
{
    /**
     * Capture the valid date string within a header containing non-standard values RFC5322 §3.3
     * https://tools.ietf.org/html/rfc5322#section-3.3
     * Eg. "Mon, 9 Dec 2019 18:36:20 +0800" from "Mon, 9 Dec 2019 18:36:20 +0800 (GMT+08:00)"
     *
     * @var string
     */
    private const DATE_REGEX = '/((?:\w{3},\s{1,2})?\d{1,2}\s\w{3}\s\d{4}\s\d{2}:\d{2}(?::\d{2})?\s(?:\+|-)\d{4})/';

    /**
     * Capture the date part separately from the rest of the header, matching RFC5322 §3.6.7
     * https://tools.ietf.org/html/rfc5322#section-3.6.7
     * Latter part matches self::DATE_REGEX
     *
     * Eg.
     * "by mta6551.example.com (PowerMTA(TM) v3.5r14)" and "Fri, 23 Sep 2011 09:54:22 +0100"
     * from:
     * "by mta6551.example.com (PowerMTA(TM) v3.5r14); Fri, 23 Sep 2011 09:54:22 +0100
     * (envelope-from <bounce-100-250-1831-live@mail.example.com>)"
     * @var string
     */
    private const RECEIVED_REGEX = '/^(.*);\s*((?:\w{3},\s{1,2})?\d{1,2}\s\w{3}\s\d{4}\s\d{2}:\d{2}(?::\d{2})?\s(?:\+|-)\d{4})/';

    /**
     * @var bool
     */
    private $isFile;

    /**
     * @var string
     */
    private $source;

    /**
     * @var resource
     */
    private $mimeMail;

    /**
     * @var array
     */
    private $structure;

    public function createFromFile(string $filename): Mail
    {
        try {
            $this->source = $filename;
            $this->isFile = true;

            if (!is_file($this->source)) {
                throw new RuntimeException("Filename '{$this->source}' cannot be found");
            }

            $result = $this->mimeMail = @mailparse_msg_parse_file($this->source);
            if ($result === false) {
                throw new RuntimeException('Email could not be read');
            }

            return $this->parseEmail();
        } finally {
            if (is_resource($this->mimeMail)) {
                mailparse_msg_free($this->mimeMail);
            }
            unset(
                $this->isFile,
                $this->source,
                $this->mimeMail,
                $this->structure
            );
        }
    }

    public function createFromString(string $source): Mail
    {
        try {
            $this->source = $source;
            $this->isFile = false;

            $this->mimeMail = mailparse_msg_create();
            $result = @mailparse_msg_parse($this->mimeMail, $this->source);

            if ($result === false) {
                throw new RuntimeException('Email could not be read');
            }

            return $this->parseEmail();
        } finally {
            if (is_resource($this->mimeMail)) {
                mailparse_msg_free($this->mimeMail);
            }
            unset(
                $this->isFile,
                $this->source,
                $this->mimeMail,
                $this->structure
            );
        }
    }

    private function parseEmail(): Mail
    {
        $mail = new Mail();

        // Headers and meta info
        $mimeData = @mailparse_msg_get_part_data($this->mimeMail);
        if ($mimeData === false || empty($mimeData)) {
            throw new RuntimeException('Email headers could not be read');
        }
        $this->addMailHeaders($mail, $mimeData['headers']);

        // Names of parts
        $this->structure = @mailparse_msg_get_structure($this->mimeMail);
        if ($this->structure === false || empty($this->structure)) {
            throw new RuntimeException('Email structure could not be read');
        }

        // Get primary email part
        $first = reset($this->structure);
        $child = $this->parsePart($first, $mail);
        $mail->setPart($child);

        return $mail;
    }

    private function addMailHeaders(Mail $mail, array $headers): void
    {
        $charset = null;

        // Iterate headers
        foreach ($headers as $headerKey => $header) {
            if (!is_array($header)) {
                $header = [$header];
            }
            foreach ($header as $headerEncoded) {
                // Decode
                $headerDecoded = $this->decodeHeader($headerEncoded, $charset);
                if ($charset === null && $headerDecoded['charset'] !== null) {
                    // Set first discovered charset
                    $charset = $headerDecoded['charset'];
                    $mail->setCharset($charset);
                }
                $headerText = $headerDecoded['text'];

                try {
                    switch (strtolower($headerKey)) {
                        case 'date':
                            if (preg_match(self::DATE_REGEX, $headerText, $dateMatch) > 0) {
                                $date = new \DateTimeImmutable($dateMatch[1]);
                            } else {
                                // Failing a match to the standard, let PHP see if it can handle it
                                try {
                                    $date = new \DateTimeImmutable($headerText);
                                } catch (\Exception $e) {
                                    break;
                                }
                            }
                            $mail->setOriginationDate($date);
                            break;
                        case 'received':
                            if (preg_match(self::RECEIVED_REGEX, $headerText, $received) > 0) {
                                $mail->addReceived(trim($received[1]), new \DateTimeImmutable($received[2]));
                            }
                            break;
                        case 'return-path':
                        case 'from':
                        case 'reply-to':
                            $addresses = $this->parseEmailAddresses($headerText);
                            $method = 'set' . str_replace(' ', '', ucwords(
                                str_replace('-', ' ', strtolower($headerKey))
                            ));
                            foreach ($addresses as $address) {
                                $mail->{$method}(
                                    $address['address'],
                                    ($address['display'] === $address['address']) ? null : $address['display']
                                );
                            }
                            break;
                        case 'to':
                        case 'cc':
                            $addresses = $this->parseEmailAddresses($headerText);
                            $method = 'add' . ucwords(strtolower($headerKey));
                            foreach ($addresses as $address) {
                                $mail->{$method}(
                                    $address['address'],
                                    ($address['display'] === $address['address']) ? null : $address['display']
                                );
                            }
                            break;
                        case 'message-id':
                            $messageId = $this->parseEmailAddresses($headerText);
                            if (!empty($messageId)) {
                                $mail->setMessageId($messageId[0]['address']);
                            }
                            break;
                        case 'in-reply-to':
                            $addresses = $this->parseEmailAddresses($headerText);
                            if (!empty($addresses)) {
                                $mail->setInReplyTo(array_column($addresses, 'address'));
                            }
                            break;
                        case 'references':
                            $addresses = $this->parseEmailAddresses($headerText);
                            if (!empty($addresses)) {
                                $mail->setReferences(array_column($addresses, 'address'));
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
    }

    private function addHeaders(AbstractPart $part, array $headers): void
    {
        $charset = null;
        if (method_exists($part, 'getCharset')) {
            $charset = $part->getCharset();
        }

        // Iterate headers
        foreach ($headers as $headerKey => $header) {
            if (!is_array($header)) {
                $header = [$header];
            }
            foreach ($header as $headerEncoded) {
                $headerDecoded = $this->decodeHeader($headerEncoded, $charset);
                $headerText = $headerDecoded['text'];
                try {
                    $part->addHeader($headerKey, $headerText);
                } catch (\InvalidArgumentException $e) {
                }
            }
        }
    }

    /**
     * Recursively parse structure parts starting from the specified part
     */
    private function parsePart(string $name, Mail $mail): AbstractPart
    {
        // Get part resource
        if (
            ($part = @mailparse_msg_get_part($this->mimeMail, $name)) === false ||
            ($partData = @mailparse_msg_get_part_data($part)) === false
        ) {
            $error = error_get_last();
            throw new RuntimeException("Unable to parse part {$name}: {$error['message']}");
        }

        // Create correct Mail part object
        $type = null;
        if (array_key_exists('content-type', $partData)) {
            $type = $partData['content-type'];
        }

        if (is_string($type) && stripos($type, 'multipart') === 0) {
            switch ($type) {
                case 'multipart/alternative':
                    $mailPart = new Mime\MultipartAlternative();
                    break;
                case 'multipart/mixed':
                    $mailPart = new Mime\MultipartMixed();
                    break;
                case 'multipart/related':
                    $mailPart = new Mime\MultipartRelated();
                    break;
                case 'multipart/report':
                    $mailPart = new Mime\MultipartReport();
                    if (array_key_exists('content-report-type', $partData)) {
                        $mailPart->setReportType($partData['content-report-type']);
                    }
                    break;
                default:
                    $mailPart = new Mime\Mime($type);
                    break;
            }

            // This part should have children
            $childId = 1;
            // Check if the next part matches the expected child name
            while (in_array("{$name}.{$childId}", $this->structure, true)) {
                $child = $this->parsePart("{$name}.{$childId}", $mail);
                $mailPart->addPart($child);

                // Calculate next
                $childId++;
            }
        } else {
            // Must be some sort of content, can't be an attachment for the primary part
            if ($name !== '1' && isset($partData['content-name'])) {
                // It's an attachment
                $mail->incrementAttachmentCount();
                $disposition = isset($partData['content-disposition']) ? $partData['content-disposition'] : null;
                $mailPart = new Content\Attachment($partData['content-name'], $disposition, $type);
                if (isset($partData['content-charset'])) {
                    $mailPart->setCharset($partData['content-charset']);
                }
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
                        $mailPart->setEncoding($partData['transfer-encoding']);
                        break;
                }
                $mailPart->setCharset($partData['charset']);
            }

            if ($this->isFile) {
                $content = @mailparse_msg_extract_part_file($part, $this->source, null);
            } else {
                $content = @mailparse_msg_extract_part($part, $this->source, null);
            }

            if ($content === false) {
                throw new RuntimeException(
                    "Content could not be parsed ({$name})"
                );
            }

            $mailPart->setContent($content);
        }

        // Add any extra headers if this isn't the primary part
        if ($name !== '1') {
            $this->addHeaders($mailPart, $partData['headers']);
        }

        return $mailPart;
    }

    /**
     * Decode header
     *
     * @param string $header Encoded header
     * @param string|null $charset Target charset. Optional. Default will use source charset where available.
     * @return array {
     *     @var string $text    Decoded header
     *     @var string $charset Charset of the decoded header
     * }
     */
    private function decodeHeader(string $header, ?string $charset = null): array
    {
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
            'text' => $header,
        ];
    }

    /**
     * Parse RFC 822 formatted email addresses string
     *
     * @see mailparse_rfc822_parse_addresses()
     * @return array 'display', 'address' and 'is_group'
     */
    private function parseEmailAddresses(string $addresses): array
    {
        return mailparse_rfc822_parse_addresses($addresses);
    }
}
