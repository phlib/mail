# phlib/mail

[![Code Checks](https://img.shields.io/github/actions/workflow/status/phlib/mail/code-checks.yml?logo=github)](https://github.com/phlib/mail/actions/workflows/code-checks.yml)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/mail.svg?logo=codecov)](https://codecov.io/gh/phlib/mail)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/mail.svg?logo=packagist)](https://packagist.org/packages/phlib/mail)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/mail.svg?logo=packagist)](https://packagist.org/packages/phlib/mail)
![Licence](https://img.shields.io/github/license/phlib/mail.svg)

Classes for working with emails

## Install

Via Composer

``` bash
$ composer require phlib/mail
```

## Usage

### Creating an email

``` php
<?php
use Phlib\Mail;

// from string
$email = (new Factory)->createFromString('... raw email');

// from file
$email = (new Factory)->createFromFile('/path/to/file.eml');
```

### Working with an email

```php

/** @var Phlib\Mail\Mail $email **/
$email->getSubject();
$email->hasHeader('X-Header-Name');
$email->setReturnPath('return-path@example.com');

// raw email
$email->toString();

```

## License

This package is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
