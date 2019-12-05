<?php

declare(strict_types=1);

namespace Phlib\Mail;

use Phlib\Mail\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\UnstructuredHeader;

abstract class AbstractPart
{
    public const ENCODING_BASE64     = 'base64';
    public const ENCODING_QPRINTABLE = 'quoted-printable';
    public const ENCODING_7BIT       = '7bit';
    public const ENCODING_8BIT       = '8bit';

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    protected $prohibitedHeaders = [
        'content-type',
        'content-transfer-encoding',
        'mime-version'
    ];

    /**
     * @var array
     */
    private $validEncodings = [
        self::ENCODING_BASE64,
        self::ENCODING_QPRINTABLE,
        self::ENCODING_7BIT,
        self::ENCODING_8BIT,
    ];

    /**
     * @var string
     */
    protected $encoding = self::ENCODING_QPRINTABLE;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $charset;

    /**
     * Add header
     *
     * @param string $name
     * @param string $value
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addHeader(string $name, string $value): self
    {
        $nameLower = strtolower($name);

        if (in_array($nameLower, $this->prohibitedHeaders)) {
            throw new InvalidArgumentException("Header name is prohibited ({$nameLower})");
        } elseif (!preg_match('/^[a-z]+[a-z0-9-]+$/', $nameLower)) {
            throw new InvalidArgumentException("Name doesn't match expected format ({$nameLower})");
        }

        $nameProper = str_replace(' ', '-', ucwords(str_replace('-', ' ', $nameLower)));
        $value = trim($value);

        if (!array_key_exists($nameLower, $this->headers)) {
            $this->headers[$nameLower] = [];
        }
        $this->headers[$nameLower][] = new UnstructuredHeader($nameProper, $value);

        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->clearHeader($name);
        $this->addHeader($name, $value);

        return $this;
    }

    public function clearHeaders(): self
    {
        $this->headers = [];
        return $this;
    }

    public function clearHeader(string $name): self
    {
        $name = strtolower($name);
        unset($this->headers[$name]);

        return $this;
    }

    public function getHeaders(): array
    {
        $values = [];
        foreach ($this->headers as $name => $headerCollection) {
            $values[$name] = [];
            /** @var HeaderInterface $header */
            foreach ($headerCollection as $header) {
                $values[$name][] = $header->getBody();
            }
        }
        return $values;
    }

    public function getHeader(string $name): array
    {
        $name = strtolower($name);
        if (!isset($this->headers[$name])) {
            return [];
        }

        $values = [];
        /** @var HeaderInterface $header */
        foreach ($this->headers[$name] as $header) {
            $values[] = $header->getBody();
        }
        return $values;
    }

    public function hasHeader(string $name): bool
    {
        $name = strtolower($name);
        if (isset($this->headers[$name]) && !empty($this->headers[$name])) {
            return true;
        }

        return false;
    }

    final public function getEncodedHeaders(): string
    {
        $headers = new Headers();

        $this->buildHeaders($headers);

        // Set correct charset on all headers
        $charset = $this->charset;
        if (!$charset) {
            $charset = mb_internal_encoding();
        }
        /** @var HeaderInterface $header */
        foreach ($headers->all() as $header) {
            // Symfony/Mime AbstractHeader defaults to 76 !?
            $header->setMaxLineLength(78);
            // Set this part's charset to the headers
            $header->setCharset($charset);
        }

        return $headers->toString();
    }

    protected function buildHeaders(Headers $headers): void
    {
        foreach ($this->headers as $name => $headerCollection) {
            foreach ($headerCollection as $header) {
                $headers->add($header);
            }
        }

        if ($this->type) {
            $contentType = $this->type;
            if ($this->charset && !($this instanceof Mime\AbstractMime)) {
                $contentType .= "; charset=\"{$this->charset}\"";
            }

            $contentType = $this->addContentTypeParameters($contentType);

            $headers->addTextHeader('Content-Type', $contentType);

            if ($this->encoding) {
                $headers->addTextHeader('Content-Transfer-Encoding', $this->encoding);
            }
        }
    }

    /**
     * Allow concrete classes to add additional content type parameters to the base value
     *
     * @param string $contentType
     * @return string
     */
    protected function addContentTypeParameters(string $contentType): string
    {
        return $contentType;
    }

    public function setCharset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Set encoding
     *
     * @param string $encoding
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setEncoding(string $encoding): self
    {
        $encoding = strtolower($encoding);
        if (!in_array($encoding, $this->validEncodings)) {
            throw new InvalidArgumentException("Invalid encoding '$encoding'");
        }
        $this->encoding = $encoding;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    abstract public function toString(): string;
}
