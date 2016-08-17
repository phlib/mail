<?php

namespace Phlib\Tests\Mail\Mime;

use Phlib\Mail\Mime\Mime;

class MimeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Mime
     */
    protected $part;

    protected function setUp()
    {
        $this->part = new Mime();
    }

    public function testSetGetType()
    {
        $type = "multipart/other";
        $this->part->setType($type);

        $this->assertEquals($type, $this->part->getType());
    }
}
