<?php

namespace Phlib\Tests\Mail\Mime;

use Phlib\Mail\Mime\Mime;

class MimeTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetType()
    {
        $type = 'multipart/other';
        $part = new Mime($type);

        $this->assertEquals($type, $part->getType());
    }
}
