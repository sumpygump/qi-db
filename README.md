qi-db
=====

Qi Db provides PHP library classes for running SQL on databases.

## Installation with Composer

Use composer to include the `Qi_Db` library in a project.

Add the following composer.json file to your project directory:

```json
{
    "require": {
        "sumpygump/qi-db": "dev-master"
    }
}
```
    
Then run composer install to fetch.

    $ composer.phar install

If you don't have composer already installed, this is my recommendation for
installing it. See
[getcomposer.org installation instructions](http://getcomposer.org/doc/00-intro.md#globally).

```
$ curl -sS https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```

Once the files have been composed with the `composer install` command, you can
use any of the `Qi_Db_` classes after composer's autoloader is included.

```php
require 'vendor/autoload.php';

$db = new Qi_Db_PdoMysql();
// ...
```

## Manual Installation

You can also download the files and place them in a library folder. If you do
this, be sure to update your autoloader to handle the `Qi_Db_*` classes or
else manually include the files of the classes you'll need.
