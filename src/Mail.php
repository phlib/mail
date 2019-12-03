<?php

declare(strict_types=1);

namespace Phlib\Mail;

use Phlib\Mail\Exception\InvalidArgumentException;
use Phlib\Mail\Exception\RuntimeException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Mime\Header\AbstractHeader;
use Symfony\Component\Mime\Header\Headers;

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
     * @var Address[]
     */
    private $to = [];

    /**
     * @var Address?
     */
    private $from;

    /**
     * @var Address[]
     */
    private $cc = [];

    /**
     * @var Address?
     */
    private $replyTo;

    /**
     * @var Address?
     */
    private $returnPath;

    /**
     * @var int The number of attachments in any of this mail's descendants
     */
    private $attachmentCount = 0;

    public function setPart(AbstractPart $part): self
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
    public function getPart(): AbstractPart
    {
        if (!$this->part) {
            throw new RuntimeException('Missing mail part');
        }
        return $this->part;
    }

    public function getEncodedHeaders(): string
    {
        $headers = new Headers();

        if ($this->returnPath) {
            $headers->addPathHeader('Return-Path', $this->returnPath);
        }

        if ($this->from) {
            $headers->addMailboxListHeader('From', [$this->from]);
        }

        if ($this->subject) {
            $headers->addTextHeader('Subject', $this->subject);
        }

        if (!empty($this->to)) {
            $headers->addMailboxListHeader('To', $this->to);
        }

        if (!empty($this->cc)) {
            $headers->addMailboxListHeader('Cc', $this->cc);
        }

        if ($this->replyTo) {
            $headers->addMailboxListHeader('Reply-To', [$this->replyTo]);
        }

        if ($this->getPart() instanceof Mime\AbstractMime) {
            $headers->addTextHeader('MIME-Version', '1.0');
        }

        // Set correct charset on all headers
        $charset = $this->charset;
        if (!$charset) {
            $charset = mb_internal_encoding();
        }
        /** @var AbstractHeader $header */
        foreach ($headers->all() as $header) {
            // Symfony/Mime AbstractHeader defaults to 76 !?
            $header->setMaxLineLength(78);
            // Set this part's charset to the headers
            $header->setCharset($charset);

        }

        $headersString = $headers->toString();

        return $headersString . parent::getEncodedHeaders();
    }

    public function addTo(string $address, ?string $name = null): self
    {
        try {
            $this->to[] = new Address($address, (string)$name);
        } catch (RfcComplianceException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    public function getTo(): array
    {
        $to = [];
        foreach ($this->to as $address) {
            $to[$address->getAddress()] = $address->getName();
        }
        return $to;
    }

    public function clearTo(): self
    {
        $this->to = [];

        return $this;
    }

    public function addCc(string $address, ?string $name = null): self
    {
        try {
            $this->cc[] = new Address($address, (string)$name);
        } catch (RfcComplianceException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    public function getCc(): array
    {
        $cc = [];
        foreach ($this->cc as $address) {
            $cc[$address->getAddress()] = $address->getName();
        }
        return $cc;
    }

    public function clearCc(): self
    {
        $this->cc = [];

        return $this;
    }

    public function setReplyTo(string $address, ?string $name = null): self
    {
        try {
            $this->replyTo = new Address($address, (string)$name);
        } catch (RfcComplianceException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    public function getReplyTo(): ?array
    {
        if ($this->replyTo === null) {
            return null;
        }

        return [
            $this->replyTo->getAddress(),
            $this->replyTo->getName(),
        ];
    }

    /**
     * Set return path
     *
     * @param string $address
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setReturnPath(string $address): self
    {
        try {
            $this->returnPath = new Address($address);
        } catch (RfcComplianceException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    public function clearReturnPath(): self
    {
        $this->returnPath = null;
        return $this;
    }

    public function getReturnPath(): ?string
    {
        if ($this->returnPath === null) {
            return null;
        }

        return $this->returnPath->getAddress();
    }

    /**
     * Set from
     *
     * @param string $address
     * @param string $name
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setFrom(string $address, ?string $name = null): self
    {
        try {
            $this->from = new Address($address, (string)$name);
        } catch (RfcComplianceException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    public function getFrom(): ?array
    {
        if ($this->from === null) {
            return null;
        }

        return [
            $this->from->getAddress(),
            $this->from->getName(),
        ];
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function hasAttachment(): bool
    {
        return ($this->attachmentCount > 0);
    }

    public function getAttachmentCount(): int
    {
        return $this->attachmentCount;
    }

    public function incrementAttachmentCount(): self
    {
        $this->attachmentCount++;

        return $this;
    }

    public function decrementAttachmentCount(): self
    {
        $this->attachmentCount--;

        return $this;
    }

    public function toString(): string
    {
        $result = $this->getEncodedHeaders() . $this->getPart()->toString();
        if (substr($result, -1) !== "\n") {
            // If mail doesn't end with a newline, then append one
            //
            // This is mostly to get around an issue when parsing the mail string back in through the Factory,
            // as mailparse cuts off the last line of the email body if it doesn't end with a newline
            // (https://bugs.php.net/bug.php?id=75923)
            //
            // A known issue with this, however, is that a mail with only a content part which is successively
            // parsed->output->parsed->output will keep gaining new lines each time
            // (unless the content is base64 encoded, as raw whitespace is discarded when decoding)
            $result .= "\r\n";
        }
        return $result;
    }
}
