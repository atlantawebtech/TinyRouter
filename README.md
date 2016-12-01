# TinyRouter

TinyRouter is a very small but powerful PHP class that routes HTTP requests based on the request URI to an anonymous function that you define.  

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

**Note: GET routes also respond to the corresponding HEAD request. Currently you can not explicitly set a HEAD route.**

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

Routes can optionally have a 'token' appended to them in this format ```{token}```. You can name the token anything you would like such as ```{id}``` or ```{name}```. Only one token is permitted in each route name and it must be the last part of the route name.

**Note: All HTTP requests are automatically redirected to the equivalent request URI with a trailing forward slash.**

### Helper Methods
There are a few public methods that are available to you within your route definition. The Closures state is bound to the Router instance which means you can access the methods via the $this keyword. 

---

```
notFoundHandler()
```

notFoundHandler() is invoked when the user tries to access an undefined route.
Which in turn is a 404 error. You can also call this method yourself within your route. There is a default 404 page with the router but you can set a custom not found handler if you would like with the next method.

---

```
setCustomNotFoundHandler(callable $callback)
```
This method overrides the default not found handler with a closure you define.

**Note: The Status-Line header is automatically set for you even in custom not found handlers.**
```php
$router = new TinyRouter\Router();
$router->setCustomNotFoundHandler(function() {
    echo 'Oops, 404 Not Found';
});
```

---

```
redirect(string $location)
```
Sets the HTTP Location header to the string you specify.

```php
$router = new TinyRouter\Router();
$router->get('/users/{id}', function($argv) {
    $user_ids = array(1, 5, 10);
    // redirect to homepage if id doesn't match a user
    if ( ! in_array($argv['id'], $user_ids) ) {
        return $this->redirect('/');
    }
    // show user profile if id exists
});
```

---

```
setHeader(string $header, string $value)
```
Sets an the HTTP header you specify to the value you pass. Example usage shown below.

```php
$router = new TinyRouter\Router();
$router->post('/api/{data}', function($argv) {
    $this->setHeader('Content-Type', 'application/json');
    echo json_encode(array('value' => $argv['data']));
});
```

---
## Credits
Syntax inspiration from the [Slim framework](https://www.slimframework.com/).
