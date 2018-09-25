<?php
declare(strict_types=1);

namespace Phlib\Mail\Content;

use Phlib\Mail\AbstractPart;
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
    protected $type = 'application/octet-stream';

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
     * @param string $disposition Optional disposition, eg. 'attachment'. NULL to ignore.
     * @return $this
     */
    public static function createFromFile(string $filename, ?string $disposition = null): Attachment
    {
        if (!is_readable($filename)) {
            throw new InvalidArgumentException('Attachment file cannot be read');
        }

        $attachment = new self(basename($filename), $disposition, mime_content_type($filename));
        $attachment->setContent(file_get_contents($filename));

        return $attachment;
    }

    /**
     * Constructor to set immutable values
     *
     * @param string $name
     * @param string $disposition Optional disposition, eg. 'attachment'. NULL to ignore.
     * @param string? $type
     */
    public function __construct(string $name, ?string $disposition = null, ?string $type = null)
    {
        $this->name = $name;
        $this->disposition = $disposition;
        if (isset($type)) {
            $this->type = $type;
        }

        // Existing disposition headers are not allowed, as disposition must be defined in construct
        $this->prohibitedHeaders[] = 'content-disposition';
    }

    /**
     * Set encoding
     *
     * @param string $encoding
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setEncoding(string $encoding): AbstractPart
    {
        if ($encoding !== 'base64') {
            throw new InvalidArgumentException('Will only accept base64 for attachment encoding');
        }

        return $this;
    }

    public function getEncodedHeaders(): string
    {
        $headers = parent::getEncodedHeaders();
        if ($this->disposition) {
            // RFC 2183
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
    protected function addContentTypeParameters(string $contentType): string
    {
        $contentType .= "; name=\"{$this->name}\"";

        return parent::addContentTypeParameters($contentType);
    }
}
