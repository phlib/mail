<?php
declare(strict_types=1);

namespace Phlib\Mail\Mime;

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
