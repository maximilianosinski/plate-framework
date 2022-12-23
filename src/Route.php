<?php
namespace PlatePHP\PlateFramework;

class Route
{

    private static array $routes = array();

    private static function add($expression, $function, $method): void
    {
        self::$routes[] = array(
            "expression" => $expression,
            "function" => $function,
            "method" => $method
        );
    }

    public static function get(string $path, $function): void
    {
        self::add($path, $function, "get");
    }
    public static function post(string $path, $function): void
    {
        self::add($path, $function, "post");
    }
    public static function put(string $path, $function): void
    {
        self::add($path, $function, "put");
    }
    public static function patch(string $path, $function): void
    {
        self::add($path, $function, "patch");
    }
    public static function delete(string $path, $function): void
    {
        self::add($path, $function, "delete");
    }
    public static function options(string $path, $function): void
    {
        self::add($path, $function, "options");
    }

    public static function run(string $base_path = "/"): void
    {
        $parsed_url = parse_url($_SERVER["REQUEST_URI"]);
        $path = $parsed_url["path"] ?? "/";
        $method = $_SERVER["REQUEST_METHOD"];
        $path_match_found = false;
        $route_match_found = false;
        foreach (self::$routes as $route) {
            if ($base_path != "" && $base_path != "/") {
                $route["expression"] = "(" . $base_path . ")" . $route["expression"];
            }
            $route["expression"] = "^" . $route["expression"];
            $route["expression"] = $route["expression"] . "$";
            if(str_contains($path, "/plate-framework/syneptic-rest-api")) {
                $path = str_replace("/plate-framework/syneptic-rest-api", "", $path);
            }
            if (preg_match("#" . $route["expression"] . "#", $path, $matches)) {
                $path_match_found = true;
                if (strtolower($method) == strtolower($route["method"])) {
                    array_shift($matches);
                    if ($base_path != "" && $base_path != "/") {
                        array_shift($matches);
                    }
                    call_user_func_array($route["function"], $matches);
                    $route_match_found = true;
                    break;
                }
            }
        }
        if (!$route_match_found) {
            if ($path_match_found) {
                $response = new Response(0, 405, "Method Not Allowed.");
            } else {
                $response = new Response(0, 404, "Not Found");
            }
            $response->json();
        }

    }

}