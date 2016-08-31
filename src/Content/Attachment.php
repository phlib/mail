<?php

namespace Phlib\Mail\Content;

use Phlib\Mail\Exception\RuntimeException;

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
    protected $type = 'application/octet-stream';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $disposition;

    /**
     * Set encoding
     *
     * @param string $encoding
     * @return \Phlib\Mail\Content\Attachment
     * @throws \InvalidArgumentException
     */
    public function setEncoding($encoding)
    {
        if ($encoding !== 'base64') {
            throw new \InvalidArgumentException('Will only accept base64 for attachment encoding');
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
            if (!$this->name) {
                throw new RuntimeException('Attachment name must be defined');
            }
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
        if (!$this->name) {
            throw new RuntimeException('Attachment name must be defined');
        }
        $contentType .= "; name=\"{$this->name}\"";

        return parent::addContentTypeParameters($contentType);
    }
}
