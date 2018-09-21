<?php
declare(strict_types=1);

namespace Phlib\Mail\Tests\Mime;

use Phlib\Mail\Mime\MultipartRelated;
use PHPUnit\Framework\TestCase;

class MultipartRelatedTest extends TestCase
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
        $type = 'multipart/related';
        $this->assertEquals($type, $this->part->getType());
    }
}
