<?php

namespace Phlib\Mail\Tests;

use Phlib\Mail\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @deprecated 2.0.4:3.0.0 Method should not have been available in the public interface
     */
    public function testDecodeHeaderUtf8Base64()
    {
        $factory = new Factory();

        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?=' . "\r\n"
            . ' =?UTF-8?B?dWl0eSBQbGFuIC0gwqMxMDAgZGlzY291bnQgdG9kYXkgb25seSE=?=';

        $expected = [
            'charset' => 'UTF-8',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    /**
     * @deprecated 2.0.4:3.0.0 Method should not have been available in the public interface
     */
    public function testDecodeHeaderIsoQ()
    {
        $factory = new Factory();

        $header = '=?ISO-8859-1?Q?London Olympics: Business Continuity Plan - =A3100 discount today only!?=';

        $expected = [
            'charset' => 'ISO-8859-1',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    /**
     * @deprecated 2.0.4:3.0.0 Method should not have been available in the public interface
     */
    public function testDecodeHeaderPart()
    {
        $factory = new Factory();

        $header = 'London Olympics: Business Continuity Plan - =?ISO-8859-1?Q?=A3100?= discount today only!';

        $expected = [
            'charset' => 'ISO-8859-1',
            'text' => 'London Olympics: Business Continuity Plan - £100 discount today only!'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    /**
     * @deprecated 2.0.4:3.0.0 Method should not have been available in the public interface
     */
    public function testDecodeHeaderMixed()
    {
        $factory = new Factory();

        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?='
            . ' =?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?=';

        $expected = [
            'charset' => 'UTF-8',
            'text' => 'London Olympics: Business ContinKeld Jørn Simonsen'
        ];

        $this->assertEquals($expected, $factory->decodeHeader($header));
    }

    /**
     * @deprecated 2.0.4:3.0.0 Method should not have been available in the public interface
     */
    public function testDecodeBrokenHeader()
    {
        $header = '=?UTF-8?B?TG9uZG9uIE9seW1waWNzOiBCdXNpbmVzcyBDb250aW4=?=' . "\r\n"
            . ' =?UTF-8?B?dWl0eSBQbGFuIC0gwqPhlibDAgZGlzY291bnQgdG9kYXkgb25seSE=?=';
        $decoded = (new Factory())->decodeHeader($header);
        $this->assertEquals('UTF-8', $decoded['charset']);
        // test that we can at least show something if the encoded word is malformed (RFC 2047 section 6.3)
        $this->assertStringStartsWith('London Olympics: Business Contin', $decoded['text']);
    }

    /**
     * @deprecated 2.0.4:3.0.0 Method should not have been available in the public interface
     */
    public function testParseEmailAddresses()
    {
        $factory = new Factory();

        $addresses = 'recipient1@example.com, "Recipient Two" <recipient2@example.com>';

        $expected = [
            0 => [
                'display'  => 'recipient1@example.com',
                'address'  => 'recipient1@example.com',
                'is_group' => false,
            ],
            1 => [
                'display'  => 'Recipient Two',
                'address'  => 'recipient2@example.com',
                'is_group' => false,
            ]
        ];

        $this->assertEquals($expected, $factory->parseEmailAddresses($addresses));
    }
}
