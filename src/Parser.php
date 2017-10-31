<?php

namespace Phlib\Mail;

use Phlib\Mail\Exception\RuntimeException;

class Parser
{
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
    private $structure = [];

    /**
     * @param boolean $isFile
     * @param string $source
     */
    public function __construct($isFile, $source)
    {
        $this->isFile = $isFile;
        $this->source = $source;

        if ($this->isFile) {
            if (!is_file($this->source)) {
                throw new RuntimeException("Filename '{$this->source}' cannot be found");
            }
            $result = $this->mimeMail = @mailparse_msg_parse_file($this->source);
        } else {
            $this->mimeMail = mailparse_msg_create();
            $result = @mailparse_msg_parse($this->mimeMail, $this->source);
        }

        if ($result === false) {
            throw new RuntimeException('Email could not be read');
        }
    }

    /**
     * Parse the email
     *
     * @return Mail
     * @throws RuntimeException
     */
    public function parseEmail()
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
        foreach ($headers as $headerKey => $header) {
            if (!is_array($header)) {
                $header = [$header];
            }
            foreach ($header as $headerEncoded) {
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
     *
     * @param string $name
     * @param Mail $mail
     * @return AbstractPart
     */
    private function parsePart($name, Mail $mail)
    {
        // Get part resource
        if (($part     = @mailparse_msg_get_part($this->mimeMail, $name)) === false ||
            ($partData = @mailparse_msg_get_part_data($part)) === false
        ) {
            $error = error_get_last();
            throw new RuntimeException("Unable to parse part $name: {$error['message']}");
        }

        // Create correct Mail part object
        $type = false;
        if (array_key_exists('content-type', $partData)) {
            $type = $partData['content-type'];
        }

        if (stripos($type, 'multipart') === 0) {
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
            while (in_array("$name.$childId", $this->structure, true)) {
                $child = $this->parsePart("$name.$childId", $mail);
                $mailPart->addPart($child);

                // Calculate next
                $childId++;
            }
        } else {
            // Must be some sort of content, can't be an attachment for the primary part
            if ($name != '1' && isset($partData['content-name'])) {
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
        if ($name != '1') {
            $this->addHeaders($mailPart, $partData['headers']);
        }

        return $mailPart;
    }

    /**
     * Decode header
     *
     * @param string $header Encoded header
     * @param string $charset Target charset. Optional. Default will use source charset where available.
     * @return array {
     *     @var string $text    Decoded header
     *     @var string $charset Charset of the decoded header
     * }
     */
    private function decodeHeader($header, $charset = null)
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
            'text' => $header
        ];
    }

    /**
     * Parse RFC 822 formatted email addresses string
     *
     * @see mailparse_rfc822_parse_addresses()
     * @param string $addresses
     * @return array 'display', 'address' and 'is_group'
     * @see mailparse_rfc822_parse_addresses
     */
    private function parseEmailAddresses($addresses)
    {
        return mailparse_rfc822_parse_addresses($addresses);
    }
}
