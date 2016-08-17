<?php

namespace Phlib\Tests\Mail\Mime;

use Phlib\Mail\Mime\MultipartAlternative;

class MultipartAlternativeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MultipartAlternative
     */
    protected $part;

    protected function setUp()
    {
        $this->part = new MultipartAlternative();
    }

    public function testGetTypeDefault()
    {
        $type = "multipart/alternative";
        $this->assertEquals($type, $this->part->getType());
    }
}
