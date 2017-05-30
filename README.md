[![Build Status](https://travis-ci.org/vitormattos/blueprint-sdk-maker.svg?branch=master)](https://travis-ci.org/vitormattos/blueprint-sdk-maker)
[![Coverage Status](https://coveralls.io/repos/vitormattos/blueprint-sdk-maker/badge.svg?branch=master&service=github)](https://coveralls.io/github/vitormattos/blueprint-sdk-maker?branch=master)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Latest Stable Version](https://poser.pugx.org/vitormattos/blueprint-sdk-maker/v/stable)](https://packagist.org/packages/vitormattos/blueprint-sdk-maker)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207-blue.svg)](https://php.net/)
[![License](https://poser.pugx.org/vitormattos/blueprint-sdk-maker/license)](https://packagist.org/packages/vitormattos/blueprint-sdk-maker)

# API Blueprint Parser

> [API Blueprint](https://apiblueprint.org/) is a powerful high-level API description language for web APIs. 

Through that's project it's possible parse `.apib` files, get all their properties and generate all files needed to push a new SDK automatically.
## How do I get started?

**NOTE:** 
*Blueprint SDK Maker depends on the [Drafter](https://github.com/apiaryio/drafter) library.
Please see that repo for build instructions.*

To generate standalone phar file, set the following in your php.ini:

```
; http://php.net/phar.readonly
phar.readonly = Off
```

If you don't need generate `phar`, run Blueprint SDK Maker using the option `--no-phar`.

### As a phar (Recommended)

Download the latest `phar` **[here](https://github.com/vitormattos/blueprint-sdk-maker/releases/latest)**.

You should put it anywhere that facilitates its accessibility (such /usr/local/bin) and chmod should be 755.
You can even rename it to just the box to avoid having to type the `.phar` extension every time.

## Parsing `.apib` files
Run the follow command replacing `<filename.apib>` by your `.apib` file.
The default output of this command is a directory called `build` containing the source of your SDK and a phar (`api.phar`) to use your SDK standalone.

```
blueprint-sdk-maker make <filename.apib>
```

## Example of using generated SDK

### From phar file
Create file called `test.php` into same directory of `api.phar` and run `test.php`
```php
<?php
use BlueprintSdk\Core\Api;

require 'api.phar';
$api = new Api();
$return = $api->Entity->getYourEndpoint('ARGUMENT-X');
var_dump($return);
```
### From composer file
Add the content of the follow `composer.json` file into `composer.json` file of your project replacing `<YourGithubAccout>` and `<TheProjectName>` for your data and run `composer install`.
```json
{
    "require" : {
        "<YourGithubAccout>/<TheProjectName>" : "dev-master"
    },
    "repositories" : [{
            "type" : "vcs",
            "url" : "https://github.com/<YourGithubAccout>/<TheProjectName>"
        }
    ]
}
```

## Contributing

If you are interested in fixing issues and contributing directly to the code
base, please see the document [How to Contribute](CONTRIBUTING.md), 

## License

Licensed under the MIT License.
