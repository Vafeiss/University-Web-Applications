<?php
declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function add(string $route, string $filePath): void
    {
        $this->routes[$route] = $filePath;
    }

    public function resolve(string $route): void
    {
        if (!isset($this->routes[$route])) {
            http_response_code(404);
            echo "404 - Route not found.";
            exit();
        }

        require_once $this->routes[$route];
        exit();
    }
}
?>