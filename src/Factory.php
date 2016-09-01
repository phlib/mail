<?php

namespace Phlib\Mail;

use Phlib\Mail\Exception\InvalidArgumentException;
use Phlib\Mail\Exception\RuntimeException;

class Factory
{
    /**
     * @var bool
     */
    private $isFile = false;

    /**
     * @var string
     */
    private $source = '';

    /**
     * @var resource
     */
    private $mimeMail = null;

    /**
     * @var array
     */
    private $structure = array();

    /**
     * Load email from file
     *
     * @param string $filename path to file
     * @return Mail
     * @throws RuntimeException
     */
    public function createFromFile($filename)
    {
        $this->source = $filename;
        $this->isFile = true;

        if (!is_file($this->source)) {
            throw new RuntimeException("Filename '{$this->source}' cannot be found");
        }

        $result = $this->mimeMail = mailparse_msg_parse_file($this->source);
        if ($result === false) {
            throw new RuntimeException("Email could not be read");
        }

        return $this->parseEmail();
    }

    /**
     * @param $filename
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
        $this->source = $source;
        $this->isFile = false;

        $this->mimeMail = mailparse_msg_create();
        $result = mailparse_msg_parse($this->mimeMail, $this->source);

        if ($result === false) {
            throw new RuntimeException("Email could not be read");
        }

        return $this->parseEmail();
    }

    /**
     * @param $source
     * @return Mail
     */
    public static function fromString($source)
    {
        return (new self)->createFromString($source);
    }

    /**
     * Parse the email
     *
     * @throws RuntimeException
     */
    private function parseEmail()
    {
        $mail = new Mail();

        // Headers and meta info
        $mimeData = mailparse_msg_get_part_data($this->mimeMail);
        if ($mimeData === false || empty($mimeData)) {
            throw new RuntimeException("Email headers could not be read");
        }
        $this->addMailHeaders($mail, $mimeData['headers']);

        // Names of parts
        $this->structure = mailparse_msg_get_structure($this->mimeMail);
        if ($this->structure === false || empty($this->structure)) {
            throw new RuntimeException("Email structure could not be read");
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
     * @param \Phlib\Mail\Mail $mail
     * @param array $headers
     */
    private function addMailHeaders(\Phlib\Mail\Mail $mail, array $headers)
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
                if (is_null($charset) && !is_null($headerDecoded['fromCharset'])) {
                    // Set first discovered charset
                    $charset = $headerDecoded['fromCharset'];
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
        $part = mailparse_msg_get_part($this->mimeMail, $name);
        $partData = mailparse_msg_get_part_data($part);

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
                    $contentType = $type;
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
            while (in_array("$name.$childId", $this->structure)) {
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
                $mailPart = new Content\Attachment($type);
                $mailPart->setName($partData['content-name']);
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
                $content = mailparse_msg_extract_part_file($part, $this->source, null);
            } else {
                $content = mailparse_msg_extract_part($part, $this->source, null);
            }

            if ($content === false) {
                throw new RuntimeException(
                    "Content could not be parsed ($name)"
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
     *
     * array(
     *   'fromCharset' => string,
     *   'toCharset' => string,
     *   'encoding' => string,
     *   'text' => string // decoded header
     * )
     *
     * @param string $header
     * @param string $toCharset
     * @return array
     */
    public function decodeHeader($header, $toCharset = null)
    {
        $result = [
            'fromCharset' => null,
            'toCharset' => null,
            'encoding' => null,
            'text' => $header
        ];

        if (preg_match('/=\?([^\?]+)\?([^\?])\?[^\?]+\?=/', $header, $matches) > 0) {
            $result['fromCharset'] = $matches[1];
            $result['encoding'] = $matches[2];
            if (is_null($toCharset)) {
                $toCharset = $matches[1];
            }
            $result['toCharset'] = $toCharset;
            $result['text'] = @iconv_mime_decode($header, 0, $toCharset);
            if ($result['text'] === false) {
                throw new InvalidArgumentException('Failed to decode header, header is not valid.');
            }
        }

        return $result;
    }

    /**
     * Parse RFC 822 formatted email addresses string
     *
     * @see mailparse_rfc822_parse_addresses()
     * @param string $addresses
     * @return array
     */
    public function parseEmailAddresses($addresses)
    {
        return mailparse_rfc822_parse_addresses($addresses);
    }
}
