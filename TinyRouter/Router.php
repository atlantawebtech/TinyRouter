<?php
/* TinyRouter version 2.0.0
** https://github.com/phptiny/TinyRouter
*/
namespace TinyRouter;

use Closure;

class Router
{
    // the http request uri
    public $request_uri;

    // the http request method (GET, POST, HEAD)
    public $request_method;   
   
    // configurations
    protected $configuration = array(
        // must be set to a callable
        'customNotFoundHandler' => null,
    );

    // GET routes
    protected $get_routes  = array();

    // POST routes
    protected $post_routes = array();

    // base path of the $request_uri
    protected $base_path;

    // array of token values
    protected $token_values = array();

    public function __construct(array $container = array())
    {
        if ( ! empty($container) ) {
            $this->setContainer($container);
        }
        $this->redirectToTrailingSlash();
        $this->request_uri    = rtrim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
        $this->request_method = $_SERVER['REQUEST_METHOD']; 
    }
   
    // defines a GET route
    public function get(string $route, callable $callback)
    {
        $tokens    = $this->getRouteTokens($route);
        $base_path = $this->stripRouteToken($route);
        $callback  = Closure::bind($callback, $this); 
        $this->get_routes[$base_path] = array('callback' => $callback, 'route' => $route, 'tokens' => $tokens);
    }

    // defines a POST route
    public function post(string $route, callable $callback)
    {
        $tokens    = $this->getRouteTokens($route);
        $base_path = $this->stripRouteToken($route);
        $callback  = Closure::bind($callback, $this); 
        $this->post_routes[$base_path] = array('callback' => $callback, 'route' => $route, 'tokens' => $tokens);
    }

    // sets the not found handler to a custom callback
    public function setCustomNotFoundHandler(callable $callback)
    {
        $callback = Closure::bind($callback, $this); 
        $this->configuration['customNotFoundHandler'] = $callback;	
    }

    // calls the not found handler
    public function notFoundHandler()
    {
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
        // if custom not found handler is set and a callable
        if (isset($this->configuration['customNotFoundHandler']) && is_callable($this->configuration['customNotFoundHandler'])) {
            return $this->configuration['customNotFoundHandler']();
        }
        echo '<h1>404 Not Found</h1>';
    }

    // sets a header value
    public function setHeader(string $header, string $value)
    {
        header("$header: $value");
    }

    // sets location header
    public function redirect(string $location)
    {
        $this->setHeader('Location', $location);
    }

    // set up dependencies
    protected function setContainer(array $container)
    {
        foreach ($container as $key => $value) {
            // if a method or property with the same name exists continue
            if (method_exists($this, $key) || property_exists($this, $key)) {
                continue;
            }
            $this->$key = $value;
        }
    }

    // redirects $request_uri to equivelent uri with a trailing slash
    protected function redirectToTrailingSlash()
    {
        if (substr(strtok($_SERVER['REQUEST_URI'], '?'), -1) != '/') {
            $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $parts    = explode('?', $_SERVER['REQUEST_URI'], 2);
            $uri      = $protocol.$_SERVER['SERVER_NAME'].$parts[0].'/'.(isset($parts[1]) ? '?'.$parts[1] : '');
            header('Location: '.$uri, true, 301);
            exit;
        } 
    }

    // returns all token names within a route
    protected function getRouteTokens(string $route)
    {
        $token_names = array();

        while($start = strpos($route, '{')) {
            $end           = strpos($route, '}');
            $start         += 1;
            $length        = $end - $start;

            $token_name    = substr($route, $start, $length);
            $token_names[] = $token_name;
            $route         = str_replace('{'.$token_name.'}', '', $route);     
        }

        return $token_names; 
    }

    // returns the route name with the token stripped (e.g. '/product/{id}' would return /product/)
    protected function stripRouteToken(string $route)
    {
        $start  = strpos($route, '{');
        // if no token return the route
        if (! $start) { return $route; }
        return substr($route, 0, $start);
    }

    // sets the base path of a route and extracts all token values
    protected function setBasePathAndTokenValues()
    {
        $url_parts    = explode('/', $this->request_uri);
        $total_parts  = count($url_parts) - 1;
        $parts_used   = 1;
        $this->base_path = '/';

        while ($parts_used < $total_parts) {
            $this->base_path .= $url_parts[$parts_used].'/';
            ++$parts_used;
            
            if (array_key_exists($this->base_path, $this->get_routes) || array_key_exists($this->base_path, $this->post_routes)) {
                break;
            }
        }
        
        $parts_left = $total_parts - $parts_used + 1;
        
        for ($i = 0; $i < $parts_left; ++$i) {
            $this->token_values[] = $url_parts[$parts_used + $i];
        }
    }

    public function run()
    {
        $this->setBasePathAndTokenValues();
        
        // HEAD requests use the same route as the corresponding GET 
        if ($this->request_method == 'GET' || $this->request_method == 'HEAD') {
            // if the route exists invoke the callback function
            if (array_key_exists($this->request_uri, $this->get_routes)) {
                return $this->get_routes[$this->request_uri]['callback'](array());
            }    
        } elseif ($this->request_method == 'POST') {
            // if the route exists invoke the callback function
            if (array_key_exists($this->request_uri, $this->post_routes)) {
                return $this->post_routes[$this->request_uri]['callback'](array());
            }    
        }

        // look for routes with tokens
        $token_route_found = false;

        // get routes
        if ($this->request_method == 'GET' || $this->request_method == 'HEAD') {
            if (array_key_exists($this->base_path, $this->get_routes)) {
                $tokens   = $this->get_routes[$this->base_path]['tokens'];
                $callback = $this->get_routes[$this->base_path]['callback'];
                $token_route_found = true;
            }
        }

        // post routes
        if ($this->request_method == 'POST') {
            if (array_key_exists($this->base_path, $this->post_routes)) {
                $tokens   = $this->post_routes[$this->base_path]['tokens'];
                $callback = $this->post_routes[$this->base_path]['callback'];
                $token_route_found = true;
            }
        }

        if ($token_route_found) {
            if (count($this->token_values) > count($tokens)) {
                return $this->notFoundHandler();
            }

            for ($i = 0; $i < count($tokens); ++$i) {
                if ( ! isset($this->token_values[$i]) ) {
                    $this->token_values[$i] = null;
                } 
            }

            $tokens_with_values = array_combine($tokens, $this->token_values);

            // invoke callback with array containing token values
            return $callback($tokens_with_values);
        }    

        // route not found - call not found handler
        $this->notFoundHandler();
    }
}
