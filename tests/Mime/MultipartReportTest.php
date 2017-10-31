<?php

namespace Phlib\Mail\Tests\Mime;

use Phlib\Mail\Mime\MultipartReport;

class MultipartReportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MultipartReport
     */
    protected $part;

    protected function setUp()
    {
        $this->part = new MultipartReport();
    }

    public function testGetTypeDefault()
    {
        $type = 'multipart/report';
        $this->assertEquals($type, $this->part->getType());
    }

    public function testGetEncodedHeaders()
    {
        $this->part->setReportType('delivery-status');

        $expected = "Content-Type: multipart/report; report-type=delivery-status\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
    }
}
