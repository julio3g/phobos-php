<?php

declare(strict_types=1);

namespace Phobos\Core;

/** Roteador simples: padrões com {param} mapeados para [Controller::class, 'metodo']. */
final class Router
{
    /** @var list<array{method:string,regex:string,handler:array{0:class-string,1:string}}> */
    private array $routes = [];

    /** @param array{0:class-string,1:string} $handler */
    public function add(string $method, string $pattern, array $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = ['method' => $method, 'regex' => $regex, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $m)) {
                continue;
            }
            // protege toda requisição que altera estado contra CSRF
            if ($method === 'POST' && !Session::checkCsrf($_POST['_csrf'] ?? null)) {
                http_response_code(419);
                echo 'Token CSRF inválido.';

                return;
            }
            [$class, $action] = $route['handler'];
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            (new $class())->$action(...array_values($params));

            return;
        }

        http_response_code(404);
        View::render('errors/404');
    }
}
