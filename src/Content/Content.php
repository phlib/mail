<?php

namespace Phlib\Mail\Content;

use Phlib\Mail\SetTypeTrait;

class Content extends AbstractContent
{
    use SetTypeTrait;

    /**
     * @var string
     */
    protected $type = 'application/octet-stream';
}
