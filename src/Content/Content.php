<?php

namespace Phlib\Mail\Content;

use Phlib\Mail\SetTypeTrait;

class Content extends AbstractContent
{
    /**
     * Constructor to set immutable values
     *
     * @param string $type
     */
    public function __construct($type = 'application/octet-stream')
    {
        $this->type = $type;
    }
}
