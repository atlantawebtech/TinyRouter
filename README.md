# TinyRouter

TinyRouter is a very small, and simple PHP class that routes HTTP requests based on the request URI to an anonymous function that you define.  

## Installation
Download the source and place it in your web root. Composer install coming soon.

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
## Contributing

## Credits
Inspiration from the way the router is used in the [Slim framework](https://www.slimframework.com/).
