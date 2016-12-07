<?php

namespace Phlib\Mail\Content;

use Phlib\Mail\Exception\InvalidArgumentException;

/**
 * Attachment class used to represent attachments as Mail content
 *
 * @package Phlib\Mail
 */
class Attachment extends AbstractContent
{
    /**
     * @var string
     */
    protected $encoding = 'base64';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $disposition;

    /**
     * Create a new Attachment part from a local file
     *
     * @param string $filename
     * @return Attachment
     */
    public static function createFromFile($filename)
    {
        if (!is_readable($filename)) {
            throw new InvalidArgumentException('Attachment file cannot be read');
        }

        $attachment = new self(basename($filename), mime_content_type($filename));
        $attachment->setContent(file_get_contents($filename));

        return $attachment;
    }

    /**
     * Constructor to set immutable values
     *
     * @var string $name
     * @param string $type
     */
    public function __construct($name, $type = 'application/octet-stream')
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Set encoding
     *
     * @param string $encoding
     * @return Attachment
     * @throws InvalidArgumentException
     */
    public function setEncoding($encoding)
    {
        if ($encoding !== 'base64') {
            throw new InvalidArgumentException('Will only accept base64 for attachment encoding');
        }

        return $this;
    }

    /**
     * Set attachment name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set disposition (eg. inline, attachment)
     *
     * @param string $disposition
     * @return $this
     */
    public function setDisposition($disposition)
    {
        $this->disposition = $disposition;

        return $this;
    }

    /**
     * Get encoded headers
     *
     * @return string
     */
    public function getEncodedHeaders()
    {
        $headers = parent::getEncodedHeaders();
        if ($this->disposition) {
            $headers .= "Content-Disposition: {$this->disposition}; filename=\"{$this->name}\"\r\n";
        }

        return $headers;
    }

    /**
     * Add additional content type parameters to the base value
     *
     * @param string $contentType
     * @return string
     */
    protected function addContentTypeParameters($contentType)
    {
        $contentType .= "; name=\"{$this->name}\"";

        return parent::addContentTypeParameters($contentType);
    }
}
