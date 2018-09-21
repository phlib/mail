<?php
declare(strict_types=1);

namespace Phlib\Mail\Tests\Content;

use Phlib\Mail\Content\Html;

class HtmlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Html
     */
    protected $part;


    protected function setUp()
    {
        $this->part = new Html();
    }

    public function testGetTypeDefault()
    {
        $type = 'text/html';
        $this->assertEquals($type, $this->part->getType());
    }
}
