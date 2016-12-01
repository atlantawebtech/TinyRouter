# TinyRouter

TinyRouter is a very small, and simple PHP class that routes HTTP requests based on the request URI to an anonymous function that you define.  

## Installation
Download the source and place it in your web root. Composer install coming soon.

**Apache Web Server Configurations**

In order to funnel all HTTP requests to one php file you'll need to add this to an .htaccess file in the same directory as index.php.
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

## Usage
Create an index.php file with the following contents:
```php
<?php

include 'Router.php';

$router = new TinyRouter\Router();

$router->get('/hello/{name}', function($argv) {
    echo 'Hello '.$argv['name'];
});

$router->run();
```

### Defining Routes
There are two types of routes you can define, **GET** routes and **POST** routes.

**GET Route**
```php
$router = new TinyRouter\Router();
$router->get('/hello/{name}', function($argv) {
    echo 'Hello '.$argv['name'];
});
```
**POST Route**
```php
$router = new TinyRouter\Router();
$router->post('/api', function($argv) {
    $this->setHeader('Content-Type', 'application/json');
    echo json_encode(array('hello' => 'world'));
});
```
### Helper Methods
There are a few public methods that are available to you within your route definition. The Closures state is bound to the Router instance which means you can access the methods via the $this keyword. 

## Credits
Syntax inspiration from the [Slim framework](https://www.slimframework.com/).
