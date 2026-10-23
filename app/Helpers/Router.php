<?php
namespace App\Helpers;

/**
 * Lightweight HTTP Router
 */
class Router {
    private array $routes = [];
    private $notFoundHandler = null;

    /**
     * Add a GET route
     */
    public function get(string $path, callable $handler) {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Add a POST route
     */
    public function post(string $path, callable $handler) {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Add a route internally
     */
    private function addRoute(string $method, string $path, callable $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Set the 404 Not Found handler
     */
    public function setNotFoundHandler(callable $handler) {
        $this->notFoundHandler = $handler;
    }

    /**
     * Dispatch the current request to the matched route
     */
    public function dispatch(string $requestUri, string $requestMethod) {
        // Strip query string from URI
        $uri = parse_url($requestUri, PHP_URL_PATH);
        
        // Ensure path ends without trailing slash (unless it's just '/')
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            // Check method match
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Convert route path to regex to handle parameters like {id}
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $uri, $matches)) {
                // Remove integer keys from matches
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Execute handler with parameters
                call_user_func_array($route['handler'], [$params]);
                return;
            }
        }

        // If no route matches, call the 404 handler
        if ($this->notFoundHandler) {
            call_user_func($this->notFoundHandler);
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }
}
