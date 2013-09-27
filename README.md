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

// Get a fresh ModelBuilder instance
$mb = App::make('ModelBuilder');

// Set the name of the model class
$mb->name('Article');

// Set the database table
$mb->table('news');

// Change the folder where to store this model
$mb->folder('/app/models');
   
// Add columns to the table, each with their own fluent interface
// depending on their type of column.
$mb->string('title')->required();
$mb->text('description')->length(50);
$mb->integer('number_of_views');

// Add relationships, each with their own fluent interface
// depending on their type of relationship.
$mb->hasOne('Category');
```

## Auto-generating and updating models
This package checks if the model exists yet in the IoC container.
If it doesn't, then the Eloquent model file is written to disk and the database table is created.

If you wanna skip the auto-generating part in your application, just set autoGenerate to 'false' in yout ModelBuilder like this:
```php
$mb->autoGenerate(false); // defaults to true;
```

During development it may be handy to keep updating your database the moment you changed your configuration.
There is an autoUpdate method in the ModelBuilder that can be set to 'true'.
```php
$mb->autoUpdate(true); // defaults to false;
```
