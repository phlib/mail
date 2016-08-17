<?php

namespace Phlib\Mail\Content;

class Content extends AbstractContent
{
    /**
     * @var string
     */
    protected $type = 'application/octet-stream';

    /**
     * Set type
     *
     * @param string $type
     * @return \Phlib\Mail\Content\Content
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
