# HyperQuest

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

This is where your description should go. Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

composer.json

Within our project’s composer.json file we need to define a new property (assuming it doesn’t exist already) named “repositories“. 
The value of the repositories property is an array of objects.
Each object containing information about the repository we want to include in our project.

``` bash
"ahmetaksoy/hyperquest": {
    "type": "vcs",
    "url": "https://github.com/ahmetaksoy/hyperquest"
},
```

Via Composer

``` bash
$ composer require ahmetaksoy/hyperquest
```

## Usage

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [Ahmet Aksoy][link-author]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/ahmetaksoy/hyperquest.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/ahmetaksoy/hyperquest.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/ahmetaksoy/hyperquest/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/ahmetaksoy/hyperquest
[link-downloads]: https://packagist.org/packages/ahmetaksoy/hyperquest
[link-travis]: https://travis-ci.org/ahmetaksoy/hyperquest
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/ahmetaksoy
[link-contributors]: ../../contributors
