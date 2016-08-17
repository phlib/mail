<?php

namespace Phlib\Tests\Mail\Content;

use Phlib\Mail\Content\Text;

class TextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Text
     */
    protected $part;

    protected function setUp()
    {
        $this->part = new Text();
    }

    public function testGetTypeDefault()
    {
        $type = "text/plain";
        $this->assertEquals($type, $this->part->getType());
    }
}
