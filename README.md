[![Build
Status](https://travis-ci.org/vitormattos/blueprint-sdk-maker.svg?branch=master)](https://travis-ci.org/vitormattos/blueprint-sdk-maker)
[![Coverage
Status](https://coveralls.io/repos/vitormattos/blueprint-sdk-maker/badge.svg?branch=master&service=github)](https://coveralls.io/github/vitormattos/blueprint-sdk-maker?branch=master)
[![Latest Stable
Version](https://poser.pugx.org/vitormattos/blueprint-sdk-maker/v/stable)](https://packagist.org/packages/vitormattos/blueprint-sdk-maker)
[![License](https://poser.pugx.org/vitormattos/blueprint-sdk-maker/license)](https://packagist.org/packages/vitormattos/blueprint-sdk-maker)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

# Parser for API blueprint files

[API Blueprint](https://apiblueprint.org/) is a powerful high-level API
description language for web APIs and generate `.apib` media files. With this
project you will parse `.apib` files and get all propertyes of file with
oriented object interface.

## How do I get started?
###As a Phar (Recommended)

Download the latest phar from the latest [release](https://github.com/vitormattos/blueprint-sdk-maker).

You may place it anywhere that will make it easier for you to access (such as /usr/local/bin) and chmod it to 755. You can even rename it to just box to avoid having to type the .phar extension every time.

## Parsing the .apib file
Run the follow command replacing `<filename.apib>` by your `.apip` file. The default output of this command is a directory called `build` containing the source of your SDK and a phar to use your SDK standalone.

```
blueprint-sdk-maker make <filename.apib>
```

## Contributing

If you are interested in fixing issues and contributing directly to the code
base, please see the document How to Contribute, 

## License

Licensed under the MIT License.
