<?php

namespace Phlib\Mail;

use Phlib\Mail\Exception\RuntimeException;

class Factory
{
    /**
     * Load email from file
     *
     * @param string $filename path to file
     * @return Mail
     * @throws RuntimeException
     */
    public function createFromFile($filename)
    {
        return (new Parser(/*isFile*/ true, $filename))->parseEmail();
    }

    /**
     * @deprecated 2.0.4:3.0.0 Use createFromFile() to avoid statics and allow for the Factory to be used in DI
     * @param string $filename
     * @return Mail
     */
    public static function fromFile($filename)
    {
        return (new self)->createFromFile($filename);
    }

    /**
     * Load email from string
     *
     * @param string $source email as string
     * @return Mail
     * @throws RuntimeException
     */
    public function createFromString($source)
    {
        return (new Parser(/*isFile*/ false, $source))->parseEmail();
    }

    /**
     * @deprecated 2.0.4:3.0.0 Use createFromString() to avoid statics and allow for the Factory to be used in DI
     * @param string $source
     * @return Mail
     */
    public static function fromString($source)
    {
        return (new self)->createFromString($source);
    }

    /**
     * Decode header
     *
     * @deprecated 2.0.4:3.0.0 Method should not have been available in the public interface
     * @param string $header Encoded header
     * @param string $charset Target charset. Optional. Default will use source charset where available.
     * @return array {
     *     @var string $text    Decoded header
     *     @var string $charset Charset of the decoded header
     * }
     */
    public function decodeHeader($header, $charset = null)
    {
        if (preg_match('/=\?([^\?]+)\?([^\?])\?[^\?]+\?=/', $header, $matches) > 0) {
            if ($charset === null) {
                $charset = $matches[1];
            }

            // Workaround for https://bugs.php.net/bug.php?id=68821
            $header = preg_replace_callback('/(=\?[^\?]+\?Q\?)([^\?]+)(\?=)/i', function ($matches) {
                return $matches[1] . str_replace('_', '=20', $matches[2]) . $matches[3];
            }, $header);

            $header = mb_decode_mimeheader($header);
        }

        return [
            'charset' => $charset,
            'text' => $header
        ];
    }

    /**
     * Parse RFC 822 formatted email addresses string
     *
     * @deprecated 2.0.4:3.0.0 Method should not have been available in the public interface
     * @see mailparse_rfc822_parse_addresses()
     * @param string $addresses
     * @return array 'display', 'address' and 'is_group'
     * @see mailparse_rfc822_parse_addresses
     */
    public function parseEmailAddresses($addresses)
    {
        return mailparse_rfc822_parse_addresses($addresses);
    }
}
