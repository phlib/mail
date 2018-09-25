<?php
declare(strict_types=1);

namespace Phlib\Mail\Tests\Content;

use Phlib\Mail\Content\Content;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    public function testGetTypeDefault()
    {
        $part = new Content();
        $this->assertEquals('application/octet-stream', $part->getType());
    }

    public function testGetTypeDefaultExplicitNull()
    {
        $type = null;
        $part = new Content($type);
        $this->assertEquals('application/octet-stream', $part->getType());
    }

    public function testSetGetType()
    {
        $type = 'text/plain';
        $part = new Content($type);
        $this->assertEquals($type, $part->getType());
    }
}
