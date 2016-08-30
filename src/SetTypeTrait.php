<?php

namespace Phlib\Mail;

/**
 * Trait to enable the content-type to be overridden
 *
 * @package Phlib\Mail
 */
trait SetTypeTrait
{
    /**
     * Set type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
