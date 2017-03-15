# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Changed
- Simplified result array for `Factory::decodeHeader()` to just contain the charset of the resulting string
- **BC break**: Added *disposition* as a constructor argument for `Attachment`
### Removed
- **BC break**: Removed `Attachment::setName()` and
  `Attachment::setDisposition()` to make the related parameters immutable

## [1.0.0] - 2017-02-07
### Added
- Apply GNU LGPLv3 software licence

## [0.0.1] - 2016-09-12
Initial Release
