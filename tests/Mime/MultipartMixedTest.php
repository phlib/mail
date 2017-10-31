<?php

namespace Phlib\Mail\Tests\Mime;

use Phlib\Mail\Mime\MultipartMixed;

class MultipartMixedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MultipartMixed
     */
    protected $part;

    protected function setUp()
    {
        $this->part = new MultipartMixed();
    }

    public function testGetTypeDefault()
    {
        $type = 'multipart/mixed';
        $this->assertEquals($type, $this->part->getType());
    }
}
