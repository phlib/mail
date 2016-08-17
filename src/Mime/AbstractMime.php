<?php

namespace Phlib\Mail\Mime;

use Phlib\Mail\AbstractPart;

abstract class AbstractMime extends AbstractPart
{
    /**
     * @type \Phlib\Mail\AbstractPart[]
     */
    private $parts = array();

    /**
     * @type string
     */
    private $boundary;

    /**
     * Set boundary
     *
     * @param string $boundary
     * @return \Phlib\Mail\Mime\AbstractMime
     */
    private function setBoundary($boundary)
    {
        $this->boundary = $boundary;
        return $this;
    }

    /**
     * Get boundary
     *
     * @return string
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * Add part
     *
     * @param \Phlib\Mail\AbstractPart $part
     * @return \Phlib\Mail\AbstractPart
     */
    public function addPart(\Phlib\Mail\AbstractPart $part)
    {
        $this->parts[] = $part;
        return $part;
    }

    /**
     * Set parts
     *
     * @param array $parts
     * @return \Phlib\Mail\Mime\AbstractMime
     */
    public function setParts(array $parts)
    {
        $this->clearParts();
        foreach ($parts as $part) {
            $this->addPart($part);
        }
        return $this;
    }

    /**
     * Clear parts
     *
     * @return \Phlib\Mail\Mime\AbstractMime
     */
    public function clearParts()
    {
        $this->parts = array();
        return $this;
    }

    /**
     * Get parts
     *
     * @return \Phlib\Mail\AbstractPart[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        $pieces = array();
        foreach ($this->getParts() as $part) {
            $pieces[] = $part->toString();
        }

        $boundaryCheck = implode("\r\n", $pieces);
        $boundary = md5(uniqid(microtime()));
        while (stripos($boundaryCheck, $boundary) !== false) {
            $boundary = md5(uniqid(microtime()));
        }
        $this->setBoundary($boundary);

        $content = "\r\n--$boundary\r\n"
            . implode("\r\n--$boundary\r\n", $pieces)
            . "\r\n--$boundary--\r\n";

        return $this->getEncodedHeaders() . $content;
    }

    /**
     * Add additional content type parameters to the base value
     *
     * @param string $contentType
     * @return string
     */
    protected function addContentTypeParameters($contentType)
    {
        if ($this->boundary) {
            $contentType .= ";\r\n\tboundary=\"{$this->boundary}\"";
        }

        return $contentType;
    }
}
