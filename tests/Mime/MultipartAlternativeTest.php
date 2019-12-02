<?php

declare(strict_types=1);

namespace Phlib\Mail\Tests\Mime;

use Phlib\Mail\Mime\MultipartAlternative;
use PHPUnit\Framework\TestCase;

class MultipartAlternativeTest extends TestCase
{
    /**
     * @var MultipartAlternative
     */
    protected $part;

    protected function setUp(): void
    {
        $this->part = new MultipartAlternative();
    }

    public function testGetTypeDefault()
    {
        $type = 'multipart/alternative';
        $this->assertEquals($type, $this->part->getType());
    }
}
