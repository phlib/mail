<?php

namespace Phlib\Mail\Mime;

use Phlib\Mail\AbstractPart;

abstract class AbstractMime extends AbstractPart
{
    /**
     * @var AbstractPart[]
     */
    private $parts = array();

    /**
     * @var string
     */
    private $boundary;

    /**
     * Set boundary
     *
     * @param string $boundary
     * @return AbstractMime
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
     * @param AbstractPart $part
     * @return AbstractPart
     */
    public function addPart(AbstractPart $part)
    {
        $this->parts[] = $part;
        return $part;
    }

    /**
     * Set parts
     *
     * @param array $parts
     * @return AbstractMime
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
     * @return AbstractMime
     */
    public function clearParts()
    {
        $this->parts = array();
        return $this;
    }

    /**
     * Get parts
     *
     * @return AbstractPart[]
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
     * Add additional content type parameters to the base value.
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
