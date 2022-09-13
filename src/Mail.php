<?php

declare(strict_types=1);

namespace Phlib\Mail;

use Phlib\Mail\Exception\InvalidArgumentException;
use Phlib\Mail\Exception\RuntimeException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Mime\Header\DateHeader;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\IdentificationHeader;
use Symfony\Component\Mime\Header\UnstructuredHeader;

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
        'return-path',
        'received',
        'date',
        'from',
        'sender',
        'reply-to',
        'to',
        'cc',
        'message-id',
        'in-reply-to',
        'references',
        'subject',
    ];

    /**
     * @var Address?
     */
    private $returnPath;

    /**
     * @var UnstructuredHeader[]
     */
    private $received = [];

    /**
     * @var DateHeader
     */
    private $originationDate;

    /**
     * @var Address?
     */
    private $from;

    /**
     * @var Address?
     */
    private $sender;

    /**
     * @var Address?
     */
    private $replyTo;

    /**
     * @var Address[]
     */
    private $to = [];

    /**
     * @var Address[]
     */
    private $cc = [];

    /**
     * @var IdentificationHeader
     */
    private $messageId;

    /**
     * @var IdentificationHeader
     */
    private $inReplyTo;

    /**
     * @var IdentificationHeader
     */
    private $references;

    /**
     * @var string?
     */
    private $subject;

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

    protected function buildHeaders(Headers $headers): void
    {
        // Add headers in order defined in RFC5322 §3.6

        // Return-path and Received are 'trace' fields so must be first - RFC5322 §3.6.7
        if ($this->returnPath) {
            $headers->addPathHeader('Return-Path', $this->returnPath);
        }
        foreach ($this->received as $received) {
            $headers->add($received);
        }

        if ($this->originationDate) {
            $headers->add($this->originationDate);
        }

        if ($this->from) {
            $headers->addMailboxListHeader('From', [$this->from]);
        }

        if ($this->replyTo) {
            $headers->addMailboxListHeader('Reply-To', [$this->replyTo]);
        }

        if (!empty($this->to)) {
            $headers->addMailboxListHeader('To', $this->to);
        }

        if (!empty($this->cc)) {
            $headers->addMailboxListHeader('Cc', $this->cc);
        }

        if ($this->messageId) {
            $headers->add($this->messageId);
        }

        if ($this->inReplyTo) {
            $inReplyToUnstructured = new UnstructuredHeader(
                $this->inReplyTo->getName(),
                $this->inReplyTo->getBodyAsString(),
            );
            $headers->add($inReplyToUnstructured);
        }

        if ($this->references) {
            $referencesUnstructured = new UnstructuredHeader(
                $this->references->getName(),
                $this->references->getBodyAsString(),
            );
            $headers->add($referencesUnstructured);
        }

        if ($this->subject) {
            $headers->addTextHeader('Subject', $this->subject);
        }

        if ($this->getPart() instanceof Mime\AbstractMime) {
            $headers->addTextHeader('MIME-Version', '1.0');
        }

        parent::buildHeaders($headers);
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

    public function addReceived(string $received, \DateTimeImmutable $dateTime): self
    {
        // RFC5322 §3.6.7 https://tools.ietf.org/html/rfc5322#section-3.6.7
        $value = $received . '; ' . $dateTime->format(\DateTime::RFC2822);
        $this->received[] = new UnstructuredHeader('Received', $value);

        return $this;
    }

    public function getReceived(): array
    {
        $received = [];
        foreach ($this->received as $header) {
            $received[] = $header->getBody();
        }
        return $received;
    }

    public function setOriginationDate(\DateTimeImmutable $originationDate): self
    {
        $this->originationDate = new DateHeader('Date', $originationDate);
        return $this;
    }

    public function getOriginationDate(): ?\DateTimeImmutable
    {
        if ($this->originationDate === null) {
            return null;
        }
        return $this->originationDate->getBody();
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

    public function setSender(string $address, ?string $name = null): self
    {
        try {
            $this->sender = new Address($address, (string)$name);
        } catch (RfcComplianceException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    public function getSender(): ?array
    {
        if ($this->sender === null) {
            return null;
        }

        return [
            $this->sender->getAddress(),
            $this->sender->getName(),
        ];
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

    public function setMessageId(string $messageId): self
    {
        try {
            $this->messageId = new IdentificationHeader('Message-Id', $messageId);
        } catch (RfcComplianceException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
        return $this;
    }

    public function getMessageId(): ?string
    {
        if ($this->messageId === null) {
            return null;
        }
        return $this->messageId->getBody()[0];
    }

    public function setInReplyTo(array $messageIds): self
    {
        try {
            $this->inReplyTo = new IdentificationHeader('In-Reply-To', $messageIds);
        } catch (RfcComplianceException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
        return $this;
    }

    public function getInReplyTo(): ?array
    {
        if ($this->inReplyTo === null) {
            return null;
        }
        return $this->inReplyTo->getBody();
    }

    public function setReferences(array $messageIds): self
    {
        try {
            $this->references = new IdentificationHeader('References', $messageIds);
        } catch (RfcComplianceException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
        return $this;
    }

    public function getReferences(): ?array
    {
        if ($this->references === null) {
            return null;
        }
        return $this->references->getBody();
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
