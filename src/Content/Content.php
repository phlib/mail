<?php
declare(strict_types=1);

namespace Phlib\Mail\Content;

class Content extends AbstractContent
{
    /**
     * @var string
     */
    protected $type = 'application/octet-stream';

    /**
     * Constructor to set immutable values
     *
     * @param string? $type
     */
    public function __construct(?string $type = null)
    {
        if (isset($type)) {
            $this->type = $type;
        }
    }
}
