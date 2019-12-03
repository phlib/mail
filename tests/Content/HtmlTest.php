<?php

declare(strict_types=1);

namespace Phlib\Mail\Tests\Content;

use Phlib\Mail\Content\Html;
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    /**
     * @var Html
     */
    protected $part;


    protected function setUp(): void
    {
        $this->part = new Html();
    }

    public function testGetTypeDefault()
    {
        $type = 'text/html';
        $this->assertEquals($type, $this->part->getType());
    }
}
