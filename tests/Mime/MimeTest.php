<?php
declare(strict_types=1);

namespace Phlib\Mail\Tests\Mime;

use Phlib\Mail\Mime\Mime;
use PHPUnit\Framework\TestCase;

class MimeTest extends TestCase
{
    public function testSetGetType()
    {
        $type = 'multipart/other';
        $part = new Mime($type);

        $this->assertEquals($type, $part->getType());
    }

    public function testTypeNotSet()
    {
        $part = new Mime();

        $this->assertNull($part->getType());
    }
}
