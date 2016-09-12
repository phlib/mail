<?php

namespace Phlib\Mail\Content;

use Phlib\Mail\AbstractPart;

abstract class AbstractContent extends AbstractPart
{
    /**
     * @var string
     */
    private $content = '';

    /**
     * Set content
     *
     * @param string $content
     * @return \Phlib\Mail\Content\AbstractContent
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Encode content
     *
     * @param string $content
     * @return string
     */
    public function encodeContent($content)
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

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        return $this->getEncodedHeaders() . "\r\n" . $this->encodeContent($this->getContent());
    }
}
