<?php

namespace Phlib\Mail;

use Phlib\Mail\Exception\InvalidArgumentException;
use Phlib\Mail\Exception\RuntimeException;

class Mail extends AbstractPart
{
    /**
     * @var AbstractPart
     */
    private $part;

    /**
     * @type string
     */
    protected $charset = 'UTF-8';

    /**
     * @var array
     */
    protected $prohibitedHeaders = [
        'content-type',
        'content-transfer-encoding',
        'mime-version',
        'subject',
        'to',
        'from',
        'cc',
        'reply-to',
        'return-path',
    ];

    /**
     * @var string|null
     */
    private $subject;

    /**
     * @var array
     */
    private $to = [];

    /**
     * @var array|null
     */
    private $from;

    /**
     * @var array
     */
    private $cc = [];

    /**
     * @var array|null
     */
    private $replyTo;

    /**
     * @var string|null
     */
    private $returnPath;

    /**
     * @var int The number of attachments in any of this mail's descendants
     */
    private $attachmentCount = 0;

    /**
     * Set part
     *
     * @param AbstractPart $part
     * @return $this
     */
    public function setPart(AbstractPart $part)
    {
        $this->part = $part;
        return $this;
    }

    /**
     * Get part
     *
     * @return AbstractPart
     * @throws RuntimeException
     */
    public function getPart()
    {
        if (!$this->part) {
            throw new RuntimeException('Missing mail part');
        }
        return $this->part;
    }

    /**
     * Get encoded headers
     *
     * @return string
     */
    public function getEncodedHeaders()
    {
        $headers = array();

        if ($this->returnPath) {
            $headers[] = "Return-Path: <$this->returnPath>";
        }

        if ($this->from) {
            list($address, $name) = $this->from;
            $headers[] = $this->encodeHeader('From: ' . $this->formatAddress($address, $name));
        }

        if ($this->subject) {
            $headers[] = $this->encodeHeader('Subject: ' . $this->subject);
        }

        if (!empty($this->to)) {
            $to = array();
            foreach ($this->to as $address => $name) {
                $to[] = $this->formatAddress($address, $name);
            }
            $headers[] = $this->encodeHeader('To: ' . rtrim(implode(",\r\n ", $to)));
        }

        if (!empty($this->cc)) {
            $cc = array();
            foreach ($this->cc as $address => $name) {
                $cc[] = $this->formatAddress($address, $name);
            }
            $headers[] = $this->encodeHeader('Cc: ' . rtrim(implode(",\r\n ", $cc)));
        }

        if ($this->replyTo) {
            list($address, $name) = $this->replyTo;
            $headers[] = $this->encodeHeader('Reply-To: ' . $this->formatAddress($address, $name));
        }

        if ($this->getPart() instanceof Mime\AbstractMime) {
            $headers[] = 'MIME-Version: 1.0';
        }

        $headersString = '';
        if (!empty($headers)) {
            $headersString = implode("\r\n", $headers) . "\r\n";
        }

        return $headersString . parent::getEncodedHeaders();
    }

    /**
     * Add to
     *
     * @param string $address
     * @param string $name
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addTo($address, $name = null)
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address ($address)");
        }
        if ($name) {
            $name = $this->filterName($name);
        }
        $this->to[$address] = $name;

        return $this;
    }

    /**
     * Get to
     *
     * @return array
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Clear any to addresses
     *
     * @return $this
     */
    public function clearTo()
    {
        $this->to = array();

        return $this;
    }

    /**
     * Add CC
     *
     * @param string $address
     * @param string $name
     * @return $this
     */
    public function addCc($address, $name = null)
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address ($address)");
        }
        if ($name) {
            $name = $this->filterName($name);
        }
        $this->cc[$address] = $name;

        return $this;
    }

    /**
     * Get cc
     *
     * @return array
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Clear cc addresses
     *
     * @return $this
     */
    public function clearCc()
    {
        $this->cc = array();

        return $this;
    }

    /**
     * Set reply to
     *
     * @param string $address
     * @param string $name
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setReplyTo($address, $name = null)
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address ($address)");
        }
        if ($name) {
            $name = $this->filterName($name);
        }
        $this->replyTo = [
            $address,
            $name
        ];
        return $this;
    }

    /**
     * Get reply to
     *
     * @return array|null
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * Filter name
     *
     * @param string $name
     * @return string
     */
    private function filterName($name)
    {
        $rule = [
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"'  => "'",
            '<'  => '[',
            '>'  => ']',
        ];

        return trim(strtr($name, $rule));
    }

    /**
     * Set return path
     *
     * @param string $address
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setReturnPath($address)
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address ($address)");
        }
        $this->returnPath = $address;
        return $this;
    }

    /**
     * Clear return path
     *
     * @return $this
     */
    public function clearReturnPath()
    {
        $this->returnPath = null;
        return $this;
    }

    /**
     * Get return path
     *
     * @return string|null
     */
    public function getReturnPath()
    {
        return $this->returnPath;
    }

    /**
     * Set from
     *
     * @param string $address
     * @param string $name
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setFrom($address, $name = null)
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address ($address)");
        }
        if ($name) {
            $name = $this->filterName($name);
        }
        $this->from = [
            $address,
            $name
        ];
        return $this;
    }

    /**
     * Get from
     *
     * @return array|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Get subject
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Format address
     *
     * @param string $address
     * @param string $name
     * @return string
     */
    public function formatAddress($address, $name = null)
    {
        if (!$name) {
            return $address;
        }

        if (strpos($name, ',') !== false) {
            return "\"$name\" <$address>";
        } else {
            return "$name <$address>";
        }
    }

    /**
     * Return true if the mail has a part descendant which is an attachment
     *
     * @return bool
     */
    public function hasAttachment()
    {
        return ($this->attachmentCount > 0);
    }

    /**
     * Return the number of attachments contains in the mail's descendants
     *
     * @return int
     */
    public function getAttachmentCount()
    {
        return $this->attachmentCount;
    }

    /**
     * Increment the number of attachments contained in this mail's descendants
     *
     * @return $this
     */
    public function incrementAttachmentCount()
    {
        $this->attachmentCount++;

        return $this;
    }

    /**
     * Decrement the number of attachments contained in this mail's descendants
     *
     * @return $this
     */
    public function decrementAttachmentCount()
    {
        $this->attachmentCount--;

        return $this;
    }

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        $result = $this->getEncodedHeaders() . $this->getPart()->toString();
        if (substr($result, -1) !== "\n") {
            // If mail doesn't end with a newline, then append one
            //
            // This is mostly to get around an issue when parsing the mail string back in through the Factory, as mailparse
            // cuts off the last line of the email body if it doesn't end with a newline (https://bugs.php.net/bug.php?id=75923)
            //
            // A known issue with this, however, is that a mail with only a content part which is successively
            // parsed->output->parsed->output will keep gaining new lines each time
            // (unless the content is base64 encoded, as raw whitespace is discarded when decoding)
            $result .= "\r\n";
        }
        return $result;
    }
}
