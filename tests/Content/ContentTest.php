<?php

namespace Phlib\Tests\Mail\Content;

use Phlib\Mail\Content\Content;

class ContentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Content
     */
    protected $part;

    protected function setUp()
    {
        $this->part = new Content();
    }

    public function testGetTypeDefault()
    {
        $type = "application/octet-stream";
        $this->assertEquals($type, $this->part->getType());
    }

    public function testSetGetType()
    {
        $type = "text/plain";
        $this->part->setType($type);

        $this->assertEquals($type, $this->part->getType());
    }
}
