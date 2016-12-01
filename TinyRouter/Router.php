<?php
namespace TinyRouter;

use Closure;

class Router
{
	// configurations
	protected $configuration = array(
		// must be set to a callable
		'customNotFoundHandler' => null,
	);

	// GET routes
	protected $get_routes  = array();

    // POST routes
	protected $post_routes = array();

    // the http request uri
	public $request_uri;

    // the http request method (GET, POST, HEAD)
	public $request_method;   

    // base path of the $request_uri
	protected $base_path;

    // value of the token based on the $request_uri
	protected $token_path;

	public function __construct()
    {
        $this->redirectToTrailingSlash();
		$this->request_uri    = rtrim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
		$this->request_method = $_SERVER['REQUEST_METHOD']; 
		$this->setBaseAndTokenPath();
    } 

    // redirects $request_uri to equivelent uri with a trailing slash
    protected function redirectToTrailingSlash()
    {
        if (substr(strtok($_SERVER['REQUEST_URI'], '?'), -1) != '/') {
            $protocol = $_SERVER['HTTPS'] ? 'https://' : 'http://';
            $parts    = explode('?', $_SERVER['REQUEST_URI'], 2);
            $uri      = $protocol.$_SERVER['SERVER_NAME'].$parts[0].'/'.(isset($parts[1]) ? '?'.$parts[1] : '');
            header('Location: '.$uri, true, 301);
            exit;
        } 
    }

	// defines a GET route
	public function get(string $route, callable $callback)
	{
		$token     = $this->getRouteTokenName($route);
		$base_path = $this->stripRouteToken($route);
		$callback  = Closure::bind($callback, $this); 
        $this->get_routes[$base_path] = array('callback' => $callback, 'route' => $route, 'token' => $token);
	}

	// defines a POST route
	public function post(string $route, callable $callback)
	{
		$token     = $this->getRouteTokenName($route);
		$base_path = $this->stripRouteToken($route);
		$callback  = Closure::bind($callback, $this); 
		$this->post_routes[$base_path] = array('callback' => $callback, 'route' => $route, 'token' => $token);
	}
	
	// sets the not found handler to a custom callback
	public function setCustomNotFoundHandler(callable $callback)
	{
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

	// returns the name of the route token if exists (e.g. '/product/{id}' would return id)
	protected function getRouteTokenName(string $route)
	{
		$start  = strpos($route, '{');
		// if no token return false
		if (! $start) { return false; }
		$start  += 1;
		$end    = strpos($route, '}');
		$length = $end - $start;
		return substr($route, $start, $length);
	}

	// returns the route name with the token stripped (e.g. '/product/{id}' would return /product)
	protected function stripRouteToken(string $route)
	{
		$start  = strpos($route, '{');
		// if no token return the route
		if (! $start) { return $route; }
		return substr($route, 0, $start);
	}

	protected function setBaseAndTokenPath()
	{
        $tokens           = explode('/', $this->request_uri);
        // set $token_path to the last element in the tokens array
		$this->token_path = $tokens[count($tokens) - 1];
        $this->base_path  = dirname($this->request_uri).'/';
	}

	public function run()
	{
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
		if (array_key_exists($this->base_path, $this->get_routes)) {
			$token    = $this->get_routes[$this->base_path]['token'];
			$callback = $this->get_routes[$this->base_path]['callback'];

            // invoke callback with array containing token values
			return $callback(array($token => $this->token_path));
		}    

		// route not found - call not found handler
		$this->notFoundHandler();
	}
}
