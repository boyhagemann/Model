Model
=====
With this package you can:

* Generate an Eloquent model as a file and export it to the database.
* Use a fluent interface to write the model specs
* Define cross relationships between models


## Install
Use [Composer] (http://getcomposer.org) to install the package into your application
```json
require {
    "boyhagemann/model": "dev-master"
}
```

Then add the following line in app/config/app.php:
```php
...
"Boyhagemann\Model\ModelServiceProvider"
...
```

## Example usage
```php
$mb = App::make('ModelBuilder');
$mb->name('News')
   ->table('news');

$mb->string('title')->required();
$mb->text('description')->length(50);

$mb->folder('/app/models');
   
```

## Auto-generating models
This package checks if the model exists yet in the IoC container.
If it doesn't, then the Eloquent model file is written to disk and the database table is created.

If you wanna skip the auto-generating part in your application, just set autoGenerate to 'false' in yout ModelBuilder like this:
```php
// Skip the auto generating of new Eloquent models
$mb->autoGenerate(false); // defaults to true;
```

## Auto-updating models
During development it may be handy to keep updating your database the moment you changed your configuration.
There is an autoUpdate method in the ModelBuilder that can be set to 'true'.
```php
$mb->autoUpdate(true); // defaults to false;
}
```
