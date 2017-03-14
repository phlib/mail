# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- `Factory` can be constructed with a config (also available on `create*()`
  methods)
- `Factory` config option *skipErrors*. Default disabled. When enabled the
  Factory will attempt to continue when encountering parse errors, to produce
  the best possible representation of the raw message.
### Changed
- Simplified result array for `Factory::decodeHeader()` to just contain the
  charset of the resulting string

## [1.0.0] - 2017-02-07
### Added
- Apply GNU LGPLv3 software licence

## [0.0.1] - 2016-09-12
Initial Release
