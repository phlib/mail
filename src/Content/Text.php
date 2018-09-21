<?php
declare(strict_types=1);

namespace Phlib\Mail\Content;

class Text extends AbstractContent
{
    /**
     * @var string
     */
    protected $type = 'text/plain';
}
