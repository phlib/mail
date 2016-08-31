<?php

namespace Phlib\Mail\Content;

use Phlib\Mail\Exception\RuntimeException;

/**
 * Attachment class used to add local files to a Mail object
 *
 * @package Phlib\Mail
 */
class AttachmentLocal extends Attachment
{
    /**
     * @var string
     */
    private $filename;

    /**
     * Set file
     *
     * @param string $filename
     * @return $this
     */
    public function setFile($filename)
    {
        $this->filename = $filename;
        $this->setName(basename($filename));

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        if (empty($this->filename)) {
            throw new RuntimeException('Filename has not been defined');
        }

        return file_get_contents($this->filename);
    }
}
