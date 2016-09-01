<?php

namespace Phlib\Mail\Mime;

use Phlib\Mail\SetTypeTrait;

class Mime extends AbstractMime
{
    /**
     * Constructor to set immutable values
     *
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }
}
