<?php
declare(strict_types=1);

namespace Phlib\Mail\Tests\Mime;

use Phlib\Mail\Mime\MultipartMixed;
use PHPUnit\Framework\TestCase;

class MultipartMixedTest extends TestCase
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
