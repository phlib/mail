<?php
declare(strict_types=1);

namespace Phlib\Mail\Mime;

class MultipartAlternative extends AbstractMime
{
    /**
     * @var string
     */
    protected $type = 'multipart/alternative';
}
