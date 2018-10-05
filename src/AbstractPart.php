<?php
declare(strict_types=1);

namespace Phlib\Mail;

use Phlib\Mail\Exception\InvalidArgumentException;

abstract class AbstractPart
{
    const ENCODING_BASE64     = 'base64';
    const ENCODING_QPRINTABLE = 'quoted-printable';
    const ENCODING_7BIT       = '7bit';
    const ENCODING_8BIT       = '8bit';

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
        $name = strtolower($name);
        if (in_array($name, $this->prohibitedHeaders)) {
            throw new InvalidArgumentException("Header name is prohibited ($name)");
        } elseif (!preg_match('/^[a-z]+[a-z0-9-]+$/i', $name)) {
            throw new InvalidArgumentException("Name doesn't match expected format ($name)");
        }

        $rule = [
            "\r" => '',
            "\n" => '',
            "\t" => '',
        ];
        $filteredValue = strtr($value, $rule);

        if (!array_key_exists($name, $this->headers)) {
            $this->headers[$name] = [];
        }
        $this->headers[$name][] = $filteredValue;

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

    public function removeHeader(string $name, string $value): self
    {
        $name = strtolower($name);
        if (array_key_exists($name, $this->headers)) {
            foreach ($this->headers[$name] as $idx => $headerValue) {
                if ($headerValue == $value) {
                    unset($this->headers[$name][$idx]);
                    break;
                }
            }
        }

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): array
    {
        $name = strtolower($name);
        if (!array_key_exists($name, $this->headers)) {
            return [];
        }
        return $this->headers[$name];
    }

    public function hasHeader(string $name): bool
    {
        $name = strtolower($name);
        if (isset($this->headers[$name]) && !empty($this->headers[$name])) {
            return true;
        }

        return false;
    }

    public function getEncodedHeaders(): string
    {
        $headers = [];

        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                $headers[] = $this->encodeHeader($name, $value);
            }
        }

        if ($this->type) {
            $contentType = $this->type;
            if ($this->charset && !($this instanceof Mime\AbstractMime)) {
                $contentType .= "; charset=\"{$this->charset}\"";
            }

            $contentType = $this->addContentTypeParameters($contentType);

            $headers[] = "Content-Type: {$contentType}";

            if ($this->encoding) {
                $headers[] = "Content-Transfer-Encoding: {$this->encoding}";
            }
        }

        if (empty($headers)) {
            return '';
        }

        return implode("\r\n", $headers) . "\r\n";
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

    protected function encodeHeader(string $name, string $value): string
    {
        $header = "$name: " . trim($value);
        // RFC5335 Internationalized Email Headers, Section 4.3 disallows UTF-8 chars for Message-Id
        // RFC5322 Internet Message Format, Section 3.6.4 has strict control on the syntax of Message-Id
        // mb_internal_encoding() does not check for this, and will encode the header value if any non-ASCII or reserved
        // characters are present (eg. '_')
        if (strtolower($name) === 'message-id') {
            return $header;
        }
        $charset = $this->charset;
        if (!$charset) {
            $charset = mb_internal_encoding();
        }
        return mb_encode_mimeheader($header, $charset);
    }

    abstract public function toString(): string;
}
