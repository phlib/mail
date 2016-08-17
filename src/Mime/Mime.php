<?php

namespace Phlib\Mail\Mime;

class Mime extends AbstractMime
{
    /**
     * Set type
     *
     * @param string $type
     * @return \Phlib\Mail\Mime\Mime
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
