<?php

namespace Phlib\Tests\Mail\Mime;

use Phlib\Mail\Mime\MultipartRelated;

class MultipartRelatedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MultipartRelated
     */
    protected $part;

    protected function setUp()
    {
        $this->part = new MultipartRelated();
    }

    public function testGetTypeDefault()
    {
        $type = "multipart/related";
        $this->assertEquals($type, $this->part->getType());
    }
}
