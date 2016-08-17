<?php

namespace Phlib\Mail\Content;

class Attachment extends AbstractContent
{
    /**
     * @type string
     */
    protected $encoding = 'base64';

    /**
     * @type string
     */
    protected $type = 'application/octet-stream';

    /**
     * @type string
     */
    private $filename;

    /**
     * @type string
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
     * Set file
     *
     * @param string $filename
     * @return \Phlib\Mail\Content\Content
     */
    public function setFile($filename)
    {
        $this->filename = $filename;
        $this->name = basename($filename);
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        if (empty($this->filename)) {
            throw new \RuntimeException('Filename has not been defined');
        }

        return file_get_contents($this->filename);
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
