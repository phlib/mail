<?php
declare(strict_types=1);

namespace Phlib\Mail\Tests;

use Phlib\Mail\AbstractPart;
use Phlib\Mail\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AbstractPartTest extends TestCase
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

    public function testAddHeaderInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid name');

        $this->part->addHeader('invalid name', 'value');
    }

    /**
     * @dataProvider dataAddHeaderProhibitedName
     */
    public function testAddHeaderProhibitedName($name)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name is prohibited');

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
        $this->assertEquals([], $actualAfter);
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
        // Multiple basic headers
        $expected = $this->addHeaders()[1];

        // Check encoding
        $value = "line1\r\nline2, high ascii > é <\r\n";
        $this->part->addHeader('Subject', $value);
        $expected .= "Subject: line1line2, high ascii > =?UTF-8?B?" . base64_encode('é <') . "?=\r\n";

        $actual = $this->part->getEncodedHeaders();
        $this->assertEquals($expected, $actual);
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
     */
    public function testSetGetEncodingInvalid($encoding)
    {
        $this->expectException(InvalidArgumentException::class);
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
                $expected .= "{$name}: {$value}\r\n";
            }
        }

        return [$data, $expected];
    }
}
