<?php

namespace Phlib\Tests\Mail;

use Phlib\Mail\AbstractPart;

class AbstractPartTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractPart
     */
    protected $part;

    protected function setUp()
    {
        $this->part = $this->getMockForAbstractClass(AbstractPart::class);
    }

    public function testAddGetHeader()
    {
        $this->part->addHeader('test', 'value1');
        $this->part->addHeader('test', 'value2');

        $expected = ['value1', 'value2'];
        $actual = $this->part->getHeader('test');

        $this->assertEquals($expected, $actual);
    }

    public function testAddHeaderFilterValue()
    {
        $this->part->addHeader('test', "va\rl\nu\te");
        $expected = ['value'];
        $actual = $this->part->getHeader('test');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage invalid name
     */
    public function testAddHeaderInvalidName()
    {
        $this->part->addHeader('invalid name', 'value');
    }

    /**
     * @dataProvider dataAddHeaderProhibitedName
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header name is prohibited
     */
    public function testAddHeaderProhibitedName($name)
    {
        $this->part->addHeader($name, 'value');
    }

    public function dataAddHeaderProhibitedName()
    {
        return [
            ['content-type'],
            ['content-transfer-encoding'],
            ['mime-version']
        ];
    }

    public function testSetHeader()
    {
        $this->part->addHeader('test', 'value1');
        $expectedBefore = ['value1'];
        $actualBefore = $this->part->getHeader('test');
        $this->assertEquals($expectedBefore, $actualBefore);

        $this->part->setHeader('test', 'value2');
        $expectedAfter = ['value2'];
        $actualAfter = $this->part->getHeader('test');
        $this->assertEquals($expectedAfter, $actualAfter);
    }

    public function testClearHeader()
    {
        $this->part->addHeader('test', 'value1');
        $expectedBefore = ['value1'];
        $actualBefore = $this->part->getHeader('test');
        $this->assertEquals($expectedBefore, $actualBefore);

        $this->part->clearHeader('test');
        $expectedAfter = [];
        $actualAfter = $this->part->getHeader('test');
        $this->assertEquals($expectedAfter, $actualAfter);
    }

    public function testRemoveHeader()
    {
        $this->part->addHeader('test', 'value1');
        $this->part->addHeader('test', 'value2');
        $expectedBefore = ['value1', 'value2'];
        $actualBefore = $this->part->getHeader('test');
        $this->assertEquals($expectedBefore, $actualBefore);

        $this->part->removeHeader('test', 'value1');
        $actualAfter = $this->part->getHeader('test');
        $this->assertNotContains('value1', $actualAfter);
        $this->assertContains('value2', $actualAfter);

        $this->part->removeHeader('test', 'value2');
        $this->assertEmpty($this->part->getHeader('test'));
        $this->assertFalse($this->part->hasHeader('test'));
    }

    public function testGetHeaders()
    {
        $expected = $this->addHeaders();

        $actual = $this->part->getHeaders();
        $this->assertEquals($expected[0], $actual);
    }

    public function testClearHeaders()
    {
        $expected = $this->addHeaders();

        $actualBefore = $this->part->getHeaders();
        $this->assertEquals($expected[0], $actualBefore);

        $this->part->clearHeaders();
        $actualAfter = $this->part->getHeaders();
        $this->assertEquals(array(), $actualAfter);
    }

    public function testHasHeaderFalse()
    {
        $actual = $this->part->hasHeader('test1');
        $this->assertEquals(false, $actual);
    }

    public function testHasHeaderTrue()
    {
        $this->addHeaders();

        $actual = $this->part->hasHeader('test1');
        $this->assertEquals(true, $actual);
    }

    public function testGetEncodedHeaders()
    {
        $expected = $this->addHeaders();

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected[1], $actual);
    }

    /**
     * @dataProvider dataSetGetEncodingValid
     */
    public function testSetGetEncodingValid($encoding)
    {
        $this->part->setEncoding($encoding);

        $this->assertEquals($encoding, $this->part->getEncoding());
    }

    public function dataSetGetEncodingValid()
    {
        return [
            [AbstractPart::ENCODING_BASE64],
            [AbstractPart::ENCODING_QPRINTABLE],
            [AbstractPart::ENCODING_7BIT],
            [AbstractPart::ENCODING_8BIT]
        ];
    }

    /**
     * @dataProvider dataSetGetEncodingInvalid
     * @expectedException \InvalidArgumentException
     */
    public function testSetGetEncodingInvalid($encoding)
    {
        $this->part->setEncoding($encoding);
    }

    public function dataSetGetEncodingInvalid()
    {
        return [
            ['invalid-encoding']
        ];
    }

    public function testGetTypeDefault()
    {
        $type = null;
        $this->assertEquals($type, $this->part->getType());
    }

    /**
     * @dataProvider dataEncodeHeaderValue
     */
    public function testEncodeHeaderValue($encoding, $value, $expected)
    {
        $this->part->setCharset('UTF-8');
        $this->part->setEncoding($encoding);
        $actual = $this->part->encodeHeaderValue($value);
        $this->assertEquals($expected, $actual);
    }

    public function dataEncodeHeaderValue()
    {
        $value = "line1\r\n"
            . "line2, high ascii > é <\r\n";

        $b64 = "=?UTF-8?B?" . base64_encode($value) . "?=";

        $qp = "=?UTF-8?Q?line1?=\r\n"
            . " =?UTF-8?Q?line2, high ascii > =C3=A9 <?=";

        $bit7 = "line1\r\n"
            . "line2, high ascii >  <\r\n";

        $bit8 = "line1\r\n"
            . "line2, high ascii >  <\r\n";

        return [
            [AbstractPart::ENCODING_BASE64, $value, $b64],
            [AbstractPart::ENCODING_QPRINTABLE, $value, $qp],
            [AbstractPart::ENCODING_7BIT, $value, $bit7],
            [AbstractPart::ENCODING_8BIT, $value, $bit8]
        ];
    }

    /**
     * Add headers to the mail object and return the expected header string
     *
     * @return array The expected header array and string
     */
    protected function addHeaders()
    {
        $data = [
            'test1' => [
                'value1',
                'value2'
            ],
            'test2' => [
                'value3',
                'value4'
            ]
        ];

        $expected = '';
        foreach ($data as $name => $values) {
            foreach ($values as $value) {
                $name = ucwords($name);
                $this->part->addHeader(ucwords($name), $value);
                $expected .= "$name: $value\r\n";
            }
        }

        return [$data, $expected];
    }
}
