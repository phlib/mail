<?php

namespace Phlib\Mail;

abstract class AbstractPart
{
    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var array
     */
    private $prohibitedHeaders = [
        'content-type',
        'content-transfer-encoding',
        'mime-version'
    ];

    /**
     * @var array
     */
    private $validEncodings = [
        'base64',
        'quoted-printable',
        '7bit',
        '8bit',
    ];

    /**
     * @var string
     */
    protected $encoding = 'quoted-printable';

    /**
     * @var string
     */
    protected $type     = null;

    /**
     * @var string
     */
    private $charset  = 'UTF-8';

    /**
     * Add header
     *
     * @param string $name
     * @param string $value
     * @return \Phlib\Mail\AbstractPart
     * @throws \InvalidArgumentException
     */
    public function addHeader($name, $value)
    {
        $name = strtolower($name);
        if (in_array($name, $this->prohibitedHeaders)) {
            throw new \InvalidArgumentException("Header name is prohibited ($name)");
        } elseif (!preg_match('/^[a-z]+[a-z0-9-]+$/i', $name)) {
            throw new \InvalidArgumentException("Name doesn't match expected format ($name)");
        }

        $rule = [
            "\r" => '',
            "\n" => '',
            "\t" => '',
        ];
        $filteredValue = strtr($value, $rule);

        if (!array_key_exists($name, $this->headers)) {
            $this->headers[$name] = array();
        }
        $this->headers[$name][] = $filteredValue;

        return $this;
    }

    /**
     * Set header
     *
     * @param string $name
     * @param string $value
     * @return \Phlib\Mail\AbstractPart
     * @throws \InvalidArgumentException
     */
    public function setHeader($name, $value)
    {
        $this->clearHeader($name);
        $this->addHeader($name, $value);

        return $this;
    }

    /**
     * Clear headers
     *
     * @return \Phlib\Mail\AbstractPart
     */
    public function clearHeaders()
    {
        $this->headers = array();
        return $this;
    }

    /**
     * Clear header
     *
     * @param string $name
     * @return \Phlib\Mail\AbstractPart
     */
    public function clearHeader($name)
    {
        $name = strtolower($name);
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * Remove header
     *
     * @param string $name
     * @param string $value
     * @return \Phlib\Mail\AbstractPart
     */
    public function removeHeader($name, $value)
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

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get header
     *
     * @param string $name
     * @return array
     */
    public function getHeader($name)
    {
        $name = strtolower($name);
        if (!array_key_exists($name, $this->headers)) {
            return [];
        }
        return $this->headers[$name];
    }

    /**
     * Has header
     *
     * @param string $name
     * @return boolean
     */
    public function hasHeader($name)
    {
        $name = strtolower($name);
        if (isset($this->headers[$name]) && !empty($this->headers[$name])) {
            return true;
        }

        return false;
    }

    /**
     * Get encoded headers
     *
     * @return string
     */
    public function getEncodedHeaders()
    {
        $headers = array();

        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                $headers[] = "$name: " . $this->encodeHeaderValue($value);
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
    protected function addContentTypeParameters($contentType)
    {
        return $contentType;
    }

    /**
     * Set charset
     *
     * @param string $charset
     * @return \Phlib\Mail\AbstractPart
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Get charset
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Get encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Set encoding
     *
     * @param string $encoding
     * @return \Phlib\Mail\AbstractPart
     */
    public function setEncoding($encoding)
    {
        $encoding = strtolower($encoding);
        if (!in_array($encoding, $this->validEncodings)) {
            throw new \InvalidArgumentException("Invalid encoding '$encoding'");
        }
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Encode header value
     *
     * @param string $value
     * @return string
     */
    public function encodeHeaderValue($value)
    {
        if (preg_match('/[\x80-\xFF]/', $value)) {
            switch ($this->encoding) {
                case 'quoted-printable':
                    $prefix = "=?{$this->charset}?Q?";
                    $suffix = '?=';

                    $encoded = rtrim(quoted_printable_encode($value));
                    $encoded = str_replace("\r\n", "$suffix\r\n $prefix", $encoded);
                    $encoded = $prefix . $encoded . $suffix;
                    break;

                case 'base64':
                    $prefix = "=?{$this->charset}?B?";
                    $suffix = '?=';
                    $length = 76 - strlen($prefix) - strlen($suffix);

                    $encoded = rtrim(chunk_split(base64_encode($value), $length, "\r\n"));
                    $encoded = str_replace("\r\n", "$suffix\r\n $prefix", $encoded);
                    $encoded = $prefix . $encoded . $suffix;
                    break;

                case '7bit':
                case '8bit':
                default:
                    return filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
                    break;
            }
        } else {
            $encoded = $value;
        }

        return $encoded;
    }

    /**
     * To string
     *
     * @return string
     */
    abstract public function toString();
}
