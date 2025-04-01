<?php
namespace Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $patterns = [
        ':int' => '(\d+)',
        ':string' => '([a-zA-Z]+)',
        ':slug' => '([a-zA-Z0-9\-]+)',
        ':any' => '([^/]+)',
        ':uuid' => '([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})'
    ];

    public function addRoute(string $method, string $uri, $handler, array $middlewares = []): self
    {
        $pattern = $this->patternToRegex($uri);

        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];

        return $this;
    }

    public function get(string $uri, $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $uri, $handler, $middlewares);
    }

    public function post(string $uri, $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $uri, $handler, $middlewares);
    }

    public function put(string $uri, $handler, array $middlewares = []): self
    {
        return $this->addRoute('PUT', $uri, $handler, $middlewares);
    }

    public function patch(string $uri, $handler, array $middlewares = []): self
    {
        return $this->addRoute('PATCH', $uri, $handler, $middlewares);
    }

    public function delete(string $uri, $handler, array $middlewares = []): self
    {
        return $this->addRoute('DELETE', $uri, $handler, $middlewares);
    }

    public function options(string $uri, $handler, array $middlewares = []): self
    {
        return $this->addRoute('OPTIONS', $uri, $handler, $middlewares);
    }

    public function group(array $middlewares, callable $callback): self
    {
        $prevMiddlewares = $this->middlewares;
        $this->middlewares = array_merge($this->middlewares, $middlewares);

        call_user_func($callback, $this);

        $this->middlewares = $prevMiddlewares;

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $request->path();

        foreach ($this->routes as $route) {
            if($route['method'] !== $method) {
                continue;
            }

            if(!preg_match('#^'. $route['pattern'] . '$#', $uri, $matches)) {
                continue;
            }

            array_shift($matches);

            $middlewares = array_merge($this->middlewares, $route['middlewares']);
            $response = $this->runMiddlewares($middlewares, $request, function ($request) use ($route, $matches) {
                return $this->runHandler($route['handler'], $request, $matches);
            });

            return $response;
        }

        return Response::notFound('Route not found ' . $uri);
    }

    private function patternToRegex(string $pattern): string
    {
        $regex = preg_quote($pattern, '#');

        foreach ($this->patterns as $key => $value) {
            $regex = str_replace(preg_quote($key, '#'), $value, $regex);
        }

        $regex = preg_replace('#{([a-zA-Z0-9_]+)}#', '([^/]+)', $regex);

        return $regex;
    }

    private function runHandler($handler, Request $request, array $params = []): Response
    {
        if(is_callable($handler)) {
            $response = call_user_func_array($handler, array_merge([$request], $params));

            if(!($response instanceof Response)) {
                if(is_array($response) || is_object($response)) {
                    return Response::ok($response);
                }

                return new Response($response);
            }

            return $response;
        }

        if(is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);

            if(!class_exists($controller)) {
                return Response::error("Controller not found: {$controller}");
            }

            $instance = new $controller();

            if(!method_exists($instance, $method)) {
                return Response::error("Method not found: {$controller}@{$method}");
            }

            $response = call_user_func_array([$instance, $method], array_merge([$request], $params));

            if(!($response instanceof Response)) {
                if (is_array($response) || is_object($response)) {
                    return Response::ok($response);
                }

                return new Response($response);
            }

            return $response;
        }

        return Response::error('Invalid route handler');
    }

    private function runMiddlewares(array $middlewares, Request $request, callable $target): Response
    {
        if(empty($middlewares)) {
            return call_user_func($target, $request);
        }

        $middleware = array_shift($middlewares);

        if(is_string($middleware)) {
            if (!class_exists($middleware)) {
                return Response::error("Middleware not found: {$middleware}");
            }
            
            $instance = new $middleware();
            
            if (!method_exists($instance, 'handle')) {
                return Response::error("Method 'handle' not found in middleware: {$middleware}");
            }
            
            return $instance->handle($request, function ($request) use ($middlewares, $target) {
                return $this->runMiddlewares($middlewares, $request, $target);
            });
        }

        if(is_callable($middleware)) {
            return $middleware($request, function ($request) use ($middlewares, $target) {
                return $this->runMiddlewares($middlewares, $request, $target);
            });
        }

        return Response::error('Invalid middleware');
    }
}
