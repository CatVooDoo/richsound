<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Csrf;
use App\Core\Session;

final class Router
{
    /**
     * @var array<string, array<string, callable|array{0: class-string, 1: string}>>
     */
    private array $routes = [];

    /**
     * @var array<string, list<array{0: string, 1: callable|array{0: class-string, 1: string}, 2: list<string>}>>
     */
    private array $paramRoutes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path    = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path    = rtrim($path, '/') ?: '/';

        if ($method === 'POST') {
            $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!Csrf::verify((string) $token)) {
                $this->handleCsrfFailure();
                return;
            }
        }

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler !== null) {
            $this->callHandler($handler);
            return;
        }

        foreach ($this->paramRoutes[$method] ?? [] as [$regex, $handler, $paramNames]) {
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches);
                foreach ($paramNames as $i => $name) {
                    $_GET[$name] = $matches[$i] ?? '';
                }
                $this->callHandler($handler);
                return;
            }
        }

        http_response_code(404);
        echo 'Page not found';
    }

    private function handleCsrfFailure(): void
    {
        $isAjax = isset($_SERVER['HTTP_X_CSRF_TOKEN'])
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        http_response_code(419);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'csrf']);
            exit;
        }

        Session::flash('error', 'Ошибка безопасности. Пожалуйста, обнови страницу и попробуй снова.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    private function callHandler(callable|array $handler): void
    {
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            [$className, $action] = $handler;
            $controller = new $className();
            $controller->{$action}(new Request());
            return;
        }

        $handler(new Request());
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $normalizedPath = rtrim($path, '/') ?: '/';

        if (!str_contains($normalizedPath, '{')) {
            $this->routes[$method][$normalizedPath] = $handler;
            return;
        }

        $paramNames = [];
        $regex = preg_replace_callback('/\{(\w+)\}/', static function (array $m) use (&$paramNames): string {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $normalizedPath);

        $this->paramRoutes[$method][] = ['~^' . $regex . '$~', $handler, $paramNames];
    }
}
