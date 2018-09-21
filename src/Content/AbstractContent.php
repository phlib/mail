<?php
declare(strict_types=1);

namespace Phlib\Mail\Content;

use Phlib\Mail\AbstractPart;

abstract class AbstractContent extends AbstractPart
{
    /**
     * @var string
     */
    private $content = '';

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function encodeContent(string $content): string
    {
        switch ($this->encoding) {
            case self::ENCODING_QPRINTABLE:
                return quoted_printable_encode($content);
                break;

            case self::ENCODING_BASE64:
                return rtrim(chunk_split(base64_encode($content), 76, "\r\n"));
                break;

            case self::ENCODING_7BIT:
            case self::ENCODING_8BIT:
            default:
                return filter_var($content, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
                break;
        }
    }

    public function toString(): string
    {
        return $this->getEncodedHeaders() . "\r\n" . $this->encodeContent($this->getContent());
    }
}
