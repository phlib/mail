<?php

namespace Phlib\Mail\Content;

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
     * Get encoded headers
     *
     * @return string
     */
    public function getEncodedHeaders()
    {
        $headers = parent::getEncodedHeaders();
        $headers .= "Content-Disposition: attachment; filename=\"{$this->name}\"\r\n";

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
        if ($this->name) {
            $contentType .= "; name=\"{$this->name}\"";
        }

        return parent::addContentTypeParameters($contentType);
    }
}
