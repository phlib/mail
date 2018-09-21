<?php
declare(strict_types=1);

namespace Phlib\Mail\Tests\Content;

use Phlib\Mail\Content\Text;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
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
        $type = 'text/plain';
        $this->assertEquals($type, $this->part->getType());
    }
}
