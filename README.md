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


**NOTE:** *Blueprint SDK Maker depends on the [Drafter](https://github.com/apiaryio/drafter) library. Please see that repo for build instructions.*

To generate standalone phar file, set the following in your php.ini:

```
; http://php.net/phar.readonly
phar.readonly = Off
```

If you don't need generate `phar`, run Blueprint SDK Maker using the option `--no-phar`.

### As a Phar (Recommended)

Download the latest phar from the latest [release](https://github.com/vitormattos/blueprint-sdk-maker/releases/latest).

You may place it anywhere that will make it easier for you to access (such as /usr/local/bin) and chmod it to 755. You can even rename it to just box to avoid having to type the .phar extension every time.

## Parsing the .apib file
Run the follow command replacing `<filename.apib>` by your `.apip` file. The default output of this command is a directory called `build` containing the source of your SDK and a phar (`api.phar`) to use your SDK standalone.

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
