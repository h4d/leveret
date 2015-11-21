<?php

namespace H4D\Leveret\Application;

use H4D\Leveret\Filter\FilterInterface;
use H4D\Leveret\Validation\ConstraintInterface;

class Route
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $pattern;
    /**
     * @var string
     */
    protected $regex;
    /**
     * @var callable
     */
    protected $action;
    /**
     * @var string
     */
    protected $controllerClassName;
    /**
     * @var string
     */
    protected $controllerActionName;
    /**
     * @var array
     */
    protected $preDispatchActions = array();
    /**
     * @var array
     */
    protected $postDispatchActions = array();
    /**
     * @var array
     */
    protected $params = array();
    /**
     * @var array
     */
    protected $namedParams = array();
    /**
     * @var array
     */
    protected $paramsPositions = array();
    /**
     * @var array
     */
    protected $paramsNames = array();
    /**
     * @var array
     */
    protected $paramsTypes = array();
    /**
     * @var array
     */
    protected $requiredParams = array();
    /**
     * @var string
     */
    protected $wildcardRegex = '#^:(\(([a-z]+)\))?([a-zA-Z0-9_]+)$#';
    /**
     * @var bool
     */
    protected $authRequired = false;
    /**
     * @var AuthenticatorInterface
     */
    protected $authenticator;
    /**
     * @var array
     */
    protected $requestConstraints = array();
    /**
     * @var array
     */
    protected $requestFilters = array();

    /**
     * @param string $pattern
     */
    public function __construct($pattern)
    {
        $this->pattern = ($pattern != '/') ? rtrim($pattern, '/') : $pattern;
        $this->regex = $this->routePatternToRegex($this->pattern);
    }

    /**
     * Factory for chaining purposes
     *
     * @param string $pattern
     *
     * @return Route
     */
    public static function create($pattern)
    {
        return new self($pattern);
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return string
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return is_string($this->name) ? $this->name : 'UnnamedRoute' ;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setRequiredParams(array $params)
    {
        $this->requiredParams = $params;

        return $this;
    }

    /**
     * @param $paramName
     *
     * @return $this
     */
    public function setRequiredParam($paramName)
    {
        if (!in_array($paramName, $this->requiredParams))
        {
            $this->requiredParams[] = $paramName;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getRequiredParams()
    {
        return $this->requiredParams;
    }

    /**
     * @return bool
     */
    public function hasRequiredParams()
    {
        return (count($this->getRequiredParams()) > 0);
    }

    /**
     * @param AuthenticatorInterface $authenticator
     *
     * @return $this
     */
    public function authRequired(AuthenticatorInterface $authenticator)
    {
        $this->authRequired = true;
        $this->authenticator = $authenticator;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasAuthRequirements()
    {
        return $this->authRequired;
    }

    /**
     * @return AuthenticatorInterface
     */
    public function getAuthenticator()
    {
        return $this->authenticator;
    }

    /**
     * @param callable $callable
     *
     * @return $this
     * @throws \Exception
     */
    public function setAction($callable)
    {
        if(!is_callable($callable))
        {
            throw new \Exception(sprintf('Param passed to %s is not callable.',
                                         __METHOD__));
        }
        $this->action = $callable;

        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return $this
     * @throws \Exception
     */
    public function addPreDispatchAction($callable)
    {
        if(!is_callable($callable))
        {
            throw new \Exception(sprintf('Param passed to %s is not callable.',
                                         __METHOD__));
        }
        $this->preDispatchActions[] = $callable;

        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return $this
     * @throws \Exception
     */
    public function addPostDispatchAction($callable)
    {
        if(!is_callable($callable))
        {
            throw new \Exception(sprintf('Param passed to %s is not callable.',
                                         __METHOD__));
        }
        $this->postDispatchActions[] = $callable;

        return $this;
    }

    /**
     * @return callable
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function getPreDispatchActions()
    {
        return $this->preDispatchActions;
    }

    /**
     * @return bool
     */
    public function hasPreDispatchActions()
    {
        return is_array($this->getPreDispatchActions())
               && count($this->getPreDispatchActions()) > 0;
    }

    /**
     * @return array
     */
    public function getPostDispatchActions()
    {
        return $this->postDispatchActions;
    }

    /**
     * @return bool
     */
    public function hasPostDispatchActions()
    {
        return is_array($this->getPostDispatchActions())
               && count($this->getPostDispatchActions()) > 0;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return array
     */
    public function getNamedParams()
    {
        return $this->namedParams;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->resetParams();
        $this->parseParams($params);

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    protected function parseParams($params)
    {
        $this->params = $this->reOrderParams($params);
        $this->namedParams = $this->extractNamedParams();

        return $this;
    }

    /**
     * @return array
     */
    protected function extractNamedParams()
    {
        $namedParams = array();
        foreach($this->getParams() as $pos => $value)
        {
            $namedParams[$this->paramsNames[$pos]] = $value;
        }

        return $namedParams;
    }

    /**
     * @param $params
     *
     * @return array
     */
    protected function reOrderParams($params)
    {
        $reordered = array();
        foreach($this->paramsPositions as $key=>$pos)
        {
            $reordered[$pos] = isset($params[$key]) ? $params[$key] : '';
        }

        return $reordered;
    }

    /**
     * @return $this
     */
    protected function resetParams()
    {
        $this->params = array();
        $this->namedParams = array();

        return $this;
    }

    /**
     * @param integer $index
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParamByPathPosition($index, $default = null)
    {
        return isset($this->getParams()[$index]) ? $this->getParams()[$index] : $default;
    }

    /**
     * @WARNING
     * Use only with routes with no repeated "names".
     * If there are more than one equal "name" in the route this method will return only the first
     * match.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed The first param that matches $type
     */
    public function getParamByName($name, $default = null)
    {
        $pos = array_search($name, $this->paramsNames);
        return $this->getParamByPathPosition($pos, $default);
    }

    /**
     * @param string $controllerClassName
     * @param string $controllerActionName
     *
     * @return $this
     */
    public function useController($controllerClassName, $controllerActionName)
    {
        $this->controllerActionName = $controllerActionName;
        $this->controllerClassName = $controllerClassName;

        return $this;
    }

    /**
     * @return string
     */
    public function getControllerClassName()
    {
        return $this->controllerClassName;
    }

    /**
     * @return string
     */
    public function getControllerActionName()
    {
        return $this->controllerActionName;
    }

    /**
     * @return bool
     */
    public function hasController()
    {
        return !is_null($this->controllerClassName);
    }


    /**
     * @param string $routePattern
     *
     * @return string
     */
    protected function routePatternToRegex($routePattern)
    {
        $parsedRoute = '';
        $auxRoute = trim($routePattern, '/');
        $pieces = explode('/', $auxRoute);
        foreach($pieces as $pos => $value)
        {
            if($this->isWildcard($value))
            {
                list($type, $name) = $this->extractTypeAndName($value);
                $this->paramsPositions[] = $pos;
                $this->paramsNames[$pos] = $name;
                $this->paramsTypes[$pos] = $type;
                $parsedRoute .= '\/' . $this->getRegexForType($type);
            }
            else
            {
                $parsedRoute .= '\/' . $value;
            }
        }

        return $parsedRoute;
    }


    /**
     * @param string $string
     *
     * @return bool
     */
    protected function isWildcard($string)
    {
        return (bool) preg_match($this->wildcardRegex, $string);
    }

    /**
     * @param string $wildcard Something like :(int)name or :name
     *
     * @return array array($type, $name)
     */
    protected function extractTypeAndName($wildcard)
    {
        preg_match($this->wildcardRegex, $wildcard, $matches);
        $name = array_pop($matches);
        $type = array_pop($matches);
        if ('' == $type) $type = 'string';
        return array($type, $name);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getRegexForType($type)
    {
        switch(strtolower($type))
        {
            case 'integer':
            case 'int':
                $regex = '([-+]?[0-9]*)';
                break;
            case 'float':
            case 'number':
                $regex = '([-+]?[0-9]*[.]?[0-9]+)';
                break;
            case 'word':
                $regex = '([\w]*)';
                break;
            case 'string':
            default:
                $regex = '([a-zA-Z0-9- _\.@]*)';
                break;
        }

        return $regex;
    }


    /**
     * @param string $paramName
     * @param ConstraintInterface|ConstraintInterface[] $constraints A Constraint or an array of Constraints
     *
     * @return $this
     * @throws \Exception
     */
    public function addRequestConstraints($paramName, $constraints)
    {
        $constraints = is_array($constraints) ? $constraints : [$constraints];
        foreach($constraints as $constraint)
        {
            if (false == $constraint instanceof ConstraintInterface)
            {
                throw new \Exception(sprintf('Invalid constraint for param "%s".', $paramName));
            }
            $this->requestConstraints[$paramName][] = $constraint;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function hasRequestConstraints()
    {
        return (count($this->requestConstraints)>0);
    }

    /**
     * @return array [Constraint]
     */
    public function getRequestConstraints()
    {
        return $this->requestConstraints;
    }

    /**
     * @param string $paramName
     * @param FilterInterface|FilterInterface[] $filters
     *
     * @return $this
     * @throws \Exception
     */
    public function addRequestFilters($paramName, $filters)
    {
        $filters = is_array($filters) ? $filters : [$filters];
        foreach($filters as $filter)
        {
            if (false == $filter instanceof FilterInterface)
            {
                throw new \Exception(sprintf('Invalid filter for param "%s".', $paramName));
            }
            $this->requestFilters[$paramName][] = $filter;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasRequestFilters()
    {
        return (count($this->requestFilters)>0);
    }

    /**
     * @return array [FilterInterface]
     */
    public function getRequestFilters()
    {
        return $this->requestFilters;
    }
}
