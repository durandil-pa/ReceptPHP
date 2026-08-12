<?php
declare(strict_types=1);

class Router
{
    private $routes = [];

    public function get(string $page, callable $action): void
    {
        $this->routes[$page] = $action;
    }

    public function dispatch(string $page): void
    {
        if(isset($this->routes[$page])){
            ($this->routes[$page])();
            return;
        }

        http_response_code(404);
        echo "404 - Sidan kunde inte hittas.";
    }
}
