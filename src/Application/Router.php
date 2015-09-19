<?php

namespace H4D\Leveret\Application;

use H4D\Leveret\Exception\RouteNotFoundException;
use H4D\Leveret\Http\Request;

class Router
{
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var array
     */
    protected $routes;
    /**
     * @var Router
     */
    protected static $instance;


    protected function __construct()
    {

    }

    /**
     * @return Router
     */
    public static function i()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param Request $request
     *
     * @return Route
     * @throws RouteNotFoundException
     */
    public function resolve(Request $request)
    {
        $matchedRoute = null;
        $registeredRoutes =
            isset($this->routes[$request->getMethod()]) ? $this->routes[$request->getMethod()] :
                array();
        if(is_array($registeredRoutes) && count($registeredRoutes) > 0)
        {
            /** @var Route $route */
            foreach($registeredRoutes as $route)
            {
                $matched = preg_match('/^' . $route->getRegex() . '$/',
                                      $request->getPath(),
                                      $matches);
                if(1 === $matched)
                {
                    $matchedRoute = clone($route);
                    array_shift($matches); // First match is the complete route
                    $matchedRoute->setParams($matches);
                    break;
                }
            }
        }

        if(is_null($matchedRoute))
        {
            throw new RouteNotFoundException (sprintf('Impossible to match route %s', $request));
        }

        return $matchedRoute;
    }

    /**
     * @param string $method
     * @param string $routePattern
     *
     * @return Route
     */
    public function registerRoute($method, $routePattern)
    {
        $route = Route::create($routePattern);
        $this->routes[$method][$route->getPattern()] = $route;

        return $this->routes[$method][$route->getPattern()];
    }
}