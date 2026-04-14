<?php

class Router {

    private $routes = [];

    public function post($path, $handler){
        $this->routes["POST"][$path] = $handler;
    }

    public function get($path, $handler){
        $this->routes["GET"][$path] = $handler;
    }

    public function resolve(){

        $method = $_SERVER["REQUEST_METHOD"];
        $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        // Support dispatcher form posts by mapping action values to route paths.
        if ($method === "POST" && isset($_POST['action']) && is_string($_POST['action'])) {
            $action = trim($_POST['action']);
            if ($action !== '') {
                $uri = '/' . ltrim($action, '/');
            }
        }

        if(isset($this->routes[$method][$uri])){

            $handler = $this->routes[$method][$uri];

            [$controller,$function] = $handler;

            $controllerObject = new $controller();

            return $controllerObject->$function();
        }

        http_response_code(404);
        echo "Not Found";

    }
}