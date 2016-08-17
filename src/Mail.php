<?php

namespace Phlib\Mail;

use Phlib\Mail\Exception\InvalidArgumentException;

class Mail extends AbstractPart
{
    /**
     * @var AbstractPart
     */
    private $part;

    /**
     * @var array
     */
    private $prohibitedHeaders = [
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
     * @var string
     */
    private $subject    = null;

    /**
     * @var array
     */
    private $to         = [];

    /**
     * @var string
     */
    private $from       = null;

    /**
     * @var array
     */
    private $cc         = [];

    /**
     * @var string
     */
    private $replyto    = null;

    /**
     * @var string
     */
    private $returnpath = null;

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
     */
    public function getPart()
    {
        if (!$this->part) {
            throw new \RuntimeException('Missing mail part');
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

        if ($this->returnpath) {
            $headers[] = "Return-Path: <$this->returnpath>";
        }

        if ($this->from) {
            list($address, $name) = $this->from;
            $headers[] = 'From: ' . $this->formatAddress($address, $name);
        }

        if ($this->subject) {
            $headers[] = 'Subject: ' . $this->encodeHeaderValue($this->subject);
        }

        if (!empty($this->to)) {
            $to = array();
            foreach ($this->to as $address => $name) {
                $to[] = $this->formatAddress($address, $name);
            }
            $headers[] = 'To: ' . rtrim(implode(",\r\n ", $to));
        }

        if (!empty($this->cc)) {
            $cc = array();
            foreach ($this->cc as $address => $name) {
                $cc[] = $this->formatAddress($address, $name);
            }
            $headers[] = 'Cc: ' . rtrim(implode(",\r\n ", $cc));
        }

        if ($this->replyto) {
            list($address, $name) = $this->replyto;
            $headers[] = 'Reply-To: ' . $this->formatAddress($address, $name);
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
        $this->replyto = [
            $address,
            $name
        ];
        return $this;
    }

    /**
     * Get reply to
     *
     * @return array
     */
    public function getReplyTo()
    {
        return $this->replyto;
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
        $this->returnpath = $address;
        return $this;
    }

    /**
     * Clear return path
     *
     * @return $this
     */
    public function clearReturnPath()
    {
        $this->returnpath = null;
        return $this;
    }

    /**
     * Get return path
     *
     * @return string
     */
    public function getReturnPath()
    {
        return $this->returnpath;
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
     * @return array
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
     * @return string
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

        $encodedName = $this->encodeHeaderValue($name);
        if ($encodedName === $name and strpos($name, ',') !== false) {
            return "\"$encodedName\" <$address>";
        } else {
            return "$encodedName <$address>";
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
        return $this->getEncodedHeaders() . $this->getPart()->toString();
    }
}
