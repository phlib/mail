<?php
declare(strict_types=1);

namespace Phlib\Mail\Content;

class Content extends AbstractContent
{
    /**
     * Constructor to set immutable values
     *
     * @param string $type
     */
    public function __construct(string $type = 'application/octet-stream')
    {
        $this->type = $type;
    }
}
