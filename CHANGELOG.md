# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Fixed
- Additional whitespace being output in PHP v8.3+ for header values which use
  encoded-words split across multiple lines.

## [4.0.4] - 2023-10-16
### Changed
- Allow Symfony v6 dependencies
### Fixed
- `In-Reply-To` and `References` headers for BC break in `Symfony/Mime` v4.4.

## [4.0.3] - 2021-07-08
### Added
- Add specific support for PHP v8
### Changed
- Add support for a space used for a single-digit date (i.e. instead of leading 
  zero) in the origination date and received headers.

## [4.0.2] - 2019-12-12
### Fixed
- Exception thrown from Factory when Original Date header value does not match
  the expected standard and cannot be parsed by PHP.
  Now try to find standards-compliant date within the string, and fall back to
  trying the string directly but ignoring any values which still fail.

## [4.0.1] - 2019-12-06
### Fixed
- *Undefined offset* PHP Notice in `Factory` when `Message-Id` header is blank.

## [4.0.0] - 2019-12-05
### Added
- *symfony/mime* added as a required dependency.
- Dedicated setter and getter methods for primary `Mail` headers: Message-Id,
  (Origination) Date, Sender, Received, In-Reply-To and References. 
### Fixed
- Encoding full mailbox header when Display Name contains extended characters.
  Now only encode the affected part of the Display Name, so the address stays
  compliant with Address Specification
  [RFC5322 §3.4](https://tools.ietf.org/html/rfc5322#section-3.4).
### Removed
- **BC break**: Removed support for PHP v7.1 and v7.2 as they are no longer
[actively supported](https://php.net/supported-versions.php) by the PHP project
- **BC break**: Removed `AbstractPart::removeHeader()` for specific name and
  value combination. All values for a single name can still be removed using
  `AbstractPart::clearHeader($name)`
- **BC break**: Removed `Mail::formatAddress()`
- **BC break**: `Mail::addHeader()` no longer allows setting Message-Id,
  (Origination) Date, Sender, Received, In-Reply-To and References.
  Use the new dedicated setter methods.

## [3.2.1] - 2019-07-30
### Fixed
- Incorrectly adding 4 spaces to folded headers. Now only uses 1 as indicated
 by [RFC5322 §2.2.3](https://tools.ietf.org/html/rfc5322#section-2.2.3).

## [3.2.0] - 2018-10-08
### Changed
- No longer encode pure-ASCII headers.
 [RFC2047 'encoded-word'](https://tools.ietf.org/html/rfc2047#section-2)
 syntax is only used when non-ASCII characters are detected.

## [3.1.0] - 2018-09-25
### Fixed
- Handle content type/report type not being set with new strict typing
- Ensure content type defaults are used when passing in explicit `null`

## [3.0.0] - 2018-09-24
### Added
- PHP 7.1 scalar type declarations and return types
### Changed
- Prevent *Message-Id* header from being encoded, it will always be rendered
  as provided
### Removed
- **BC break**: Removed support for PHP 5.x and 7.0 as they are no longer
[actively supported](https://php.net/supported-versions.php) by the PHP project
- **BC break**: Removed deprecated methods:
    - `Factory::fromFile()`
    - `Factory::fromString()`
    - `Factory::decodeHeader()`
    - `Factory::parseEmailAddresses()`
- **BC break**: Removed `AbstractPart::encodeHeader()`

## [2.1.1] - 2018-03-19
### Fixed
- Add a trailing newline when outputting a `Mail` object to string to ensure we
can parse it back in correctly in `Factory`

## [2.1.0] - 2017-11-02
### Fixed
- Free internal Mailparse resources once the email has been parsed. This should
help PHP to free up memory.
### Changed
- Clarify the return type for the following `Mail` methods to show they can be
null if not set: `getFrom()`, `getReplyTo()`, `getReturnPath()`, `getSubject()`
### Deprecated
- `Factory::decodeHeader()` and `Factory::parseEmailAddresses()` should not have
originally formed part of the public API, and will be removed in the next major
version 
- `Factory::fromFile()` and `Factory::fromString()` static methods will be
removed in the next major version. Instead use `createFromFile()` and
`createFromString()` to avoid statics and allow for the Factory to be used in
dependency injection. 

## [2.0.3] - 2017-08-15
### Changed
- Ensure all calls to mailparse functions are silenced for warnings, and handle false returns

## [2.0.2] - 2017-08-15
### Fixed
- Correctly handle emails with 9 mime sub-parts (issue #10)

## [2.0.1] - 2017-03-15
### Fixed
- Add encoding for address headers (To, Cc, Reply-To) which was lost in 2.0.0

## [2.0.0] - 2017-03-15
### Added
- **BC break**: Added new dependency on mbstring extension
### Changed
- **BC break**: Simplified result array for `Factory::decodeHeader()` to just
  contain the charset of the resulting string
- **BC break**: Added *disposition* as a constructor argument for `Attachment`
- **BC break**: Changed `AbstractPart::encodeHeaderValue` to `AbstractPart::encodeHeader`
  requiring the whole header (name and value) to be passed for encoding
### Removed
- **BC break**: Removed `Attachment::setName()` and
  `Attachment::setDisposition()` to make the related parameters immutable
### Fixed
- Malformed encoded words in header values no longer throw errors
- Multi-byte header values are now encoded properly in encoded words without
   splitting characters
- Header values are kept to 75 characters per line (including the header name)

## [1.0.0] - 2017-02-07
### Added
- Apply GNU LGPLv3 software licence

## [0.0.1] - 2016-09-12
Initial Release
