<?php
declare(strict_types=1);

namespace Phlib\Mail;

use Phlib\Mail\Exception\InvalidArgumentException;
use Phlib\Mail\Exception\RuntimeException;
use Symfony\Component\Mime\Address;
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
     * @var string[]
     */
    private $to = [];

    /**
     * @var string[]|null
     */
    private $from;

    /**
     * @var string[]
     */
    private $cc = [];

    /**
     * @var string[]|null
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
            $headers->addPathHeader('Return-Path', new Address($this->returnPath));
        }

        if ($this->from) {
            list($address, $name) = $this->from;
            $fromAddress = new Address($address, (string)$name);
            $headers->addMailboxListHeader('From', [$fromAddress]);
        }

        if ($this->subject) {
            $headers->addTextHeader('Subject', $this->subject);
        }

        if (!empty($this->to)) {
            $to = [];
            foreach ($this->to as $address => $name) {
                $to[] = new Address($address, (string)$name);
            }
            $headers->addMailboxListHeader('To', $to);
        }

        if (!empty($this->cc)) {
            $cc = [];
            foreach ($this->cc as $address => $name) {
                $cc[] = new Address($address, (string)$name);
            }
            $headers->addMailboxListHeader('Cc', $cc);
        }

        if ($this->replyTo) {
            list($address, $name) = $this->replyTo;
            $replyTo = new Address($address, (string)$name);
            $headers->addMailboxListHeader('Reply-To', [$replyTo]);
        }

        if ($this->getPart() instanceof Mime\AbstractMime) {
            $headers->addTextHeader('MIME-Version', '1.0');
        }

        // Set correct charset on all headers
        if ($this->charset) {
            /** @var AbstractHeader $header */
            foreach ($headers->all() as $header) {
                $header->setCharset($this->charset);
            }
        }

        $headersString = $headers->toString();

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
    public function addTo(string $address, ?string $name = null): self
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

    public function getTo(): array
    {
        return $this->to;
    }

    public function clearTo(): self
    {
        $this->to = [];

        return $this;
    }

    public function addCc(string $address, ?string $name = null): self
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

    public function getCc(): array
    {
        return $this->cc;
    }

    public function clearCc(): self
    {
        $this->cc = [];

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
    public function setReplyTo(string $address, ?string $name = null): self
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

    public function getReplyTo(): ?array
    {
        return $this->replyTo;
    }

    private function filterName(string $name): string
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
    public function setReturnPath(string $address): self
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address ($address)");
        }
        $this->returnPath = $address;
        return $this;
    }

    public function clearReturnPath(): self
    {
        $this->returnPath = null;
        return $this;
    }

    public function getReturnPath(): ?string
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
    public function setFrom(string $address, ?string $name = null): self
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

    public function getFrom(): ?array
    {
        return $this->from;
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

    public function formatAddress(string $address, ?string $name = null): string
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
