<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Router sederhana: mencocokkan METHOD + path pattern ("/todos/{id}")
 * ke handler. Path cocok tapi method salah -> 405 + header Allow.
 */
final class Router
{
    /** @var list<array{method:string,regex:string,handler:callable,auth:bool}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler, bool $auth = true): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';

        $this->routes[] = [
            'method'  => strtoupper($method),
            'regex'   => $regex,
            'handler' => $handler,
            'auth'    => $auth,
        ];
    }

    /**
     * @return array{handler:callable,params:array<string,string>,auth:bool}
     */
    public function match(Request $request): array
    {
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $m)) {
                continue;
            }

            if ($route['method'] === $request->method) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

                return ['handler' => $route['handler'], 'params' => $params, 'auth' => $route['auth']];
            }

            $allowed[] = $route['method'];
        }

        if ($allowed !== []) {
            throw new ApiException(
                405,
                'HTTP method tidak diizinkan untuk endpoint ini.',
                headers: ['Allow' => implode(', ', array_unique($allowed))],
            );
        }

        throw new ApiException(404, 'Endpoint tidak ditemukan.');
    }
}
