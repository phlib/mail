<?php

declare(strict_types=1);

namespace Phlib\Mail\Mime;

use Phlib\Mail\AbstractPart;
use Symfony\Component\Mime\Header\ParameterizedHeader;

abstract class AbstractMime extends AbstractPart
{
    /**
     * @var AbstractPart[]
     */
    private $parts = [];

    /**
     * @var string
     */
    private $boundary;

    private function setBoundary(string $boundary): self
    {
        $this->boundary = $boundary;
        return $this;
    }

    public function getBoundary(): ?string
    {
        return $this->boundary;
    }

    public function addPart(AbstractPart $part): AbstractPart
    {
        $this->parts[] = $part;
        return $part;
    }

    public function setParts(array $parts): self
    {
        $this->clearParts();
        foreach ($parts as $part) {
            $this->addPart($part);
        }
        return $this;
    }

    public function clearParts(): self
    {
        $this->parts = [];
        return $this;
    }

    /**
     * Get parts
     *
     * @return AbstractPart[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    public function toString(): string
    {
        $pieces = [];
        foreach ($this->getParts() as $part) {
            $pieces[] = $part->toString();
        }

        $boundaryCheck = implode("\r\n", $pieces);
        $boundary = md5(uniqid(microtime()));
        while (stripos($boundaryCheck, $boundary) !== false) {
            $boundary = md5(uniqid(microtime()));
        }
        $this->setBoundary($boundary);

        $content = "\r\n--{$boundary}\r\n"
            . implode("\r\n--{$boundary}\r\n", $pieces)
            . "\r\n--{$boundary}--\r\n";

        return $this->getEncodedHeaders() . $content;
    }

    protected function addContentTypeParameters(ParameterizedHeader $contentTypeHeader): void
    {
        if ($this->boundary) {
            $contentTypeHeader->setParameter('boundary', $this->boundary);
        }

        parent::addContentTypeParameters($contentTypeHeader);
    }
}
