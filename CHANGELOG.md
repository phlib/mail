# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Changed
- Clarify the return type for the following `Mail` methods to show they can be
null if not set: `getFrom()`, `getReplyTo()`, `getReturnPath()`, `getSubject()` 

## [2.0.3]
### Changed
- Ensure all calls to mailparse functions are silenced for warnings, and handle false returns

## [2.0.2]
### Fixed
- Correctly handle emails with 9 mime sub-parts (issue #10)

## [2.0.1]
### Fixed
- Add encoding for address headers (To, Cc, Reply-To) which was lost in 2.0.0

## [2.0.0]
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
