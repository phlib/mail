<?php

namespace Phlib\Tests\Mail\Content;

use Phlib\Mail\Content\Content;

class ContentTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTypeDefault()
    {
        $part = new Content();
        $this->assertEquals('application/octet-stream', $part->getType());
    }

    public function testSetGetType()
    {
        $type = 'text/plain';
        $part = new Content($type);
        $this->assertEquals($type, $part->getType());
    }
}
