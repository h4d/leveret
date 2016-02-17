<?php

namespace H4D\Leveret\Application\Config;

use H4D\Leveret\Exception\ConfigRouteException;

class Route
{
    const KEY_PATTERN  = 'pattern';
    const KEY_METHOD   = 'method';
    const KEY_CALLBACK = 'callback';
    const KEY_OPTIONS  = 'options';

    const CONTROLLER_ACTION_DELIMITER = '::';

    const CALLBACK_KEY_CONTROLLER     = 'controller';
    const CALLBACK_KEY_ACTION         = 'action';
    const CALLBACK_KEY_METHOD         = 'method';

    /**
     * @var array
     */
    protected static $requiredKeys = [self::KEY_PATTERN, self::KEY_METHOD, self::KEY_CALLBACK];

    /**
     * @var string
     */
    protected $pattern;
    /**
     * @var string
     */
    protected $method;
    /**
     * @var string
     */
    protected $callback;
    /**
     * @var array
     */
    protected $options = [];
    /**
     * @var array
     */
    protected $callbackData = [];

    /**
     * Route constructor.
     *
     * @param string $method
     * @param string $pattern
     * @param string $callback
     * @param array $options
     */
    public function __construct($method, $pattern, $callback, $options = [])
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->callbackData = $this->parseCallback($this->callback);
        $this->options = $options;
    }


    /**
     * @param array $data
     *
     * @return Route
     * @throws ConfigRouteException
     */
    public static function create(array $data)
    {
        foreach(self::$requiredKeys as $key)
        {
            if (!isset($data[$key]))
            {
                throw new ConfigRouteException(
                    sprintf('Missing required key "%s" for config route creation!', $key));
            }
        }
        return new self($data[self::KEY_METHOD], $data[self::KEY_PATTERN], $data[self::KEY_CALLBACK],
                        (isset($data[self::KEY_OPTIONS]) && is_array($data[self::KEY_OPTIONS]))
                            ? $data[self::KEY_OPTIONS] : []);
    }

    /**
     * @param string $callback
     *
     * @return array
     */
    protected function parseCallback($callback)
    {
        $parsedData = [self::CALLBACK_KEY_CONTROLLER=>null,
                       self::CALLBACK_KEY_ACTION=>null,
                       self::CALLBACK_KEY_METHOD=>null];
        $callbackPieces = explode(self::CONTROLLER_ACTION_DELIMITER, $callback, 2);
        if (2 == count($callbackPieces))
        {
            $parsedData[self::CALLBACK_KEY_CONTROLLER] = $callbackPieces[0];
            $parsedData[self::CALLBACK_KEY_ACTION] = $callbackPieces[1];
        }
        else
        {
            $parsedData[self::CALLBACK_KEY_METHOD] = $callbackPieces[0];
        }

        return $parsedData;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param string $pattern
     *
     * @return Route
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getOption($name, $defaultValue = null)
    {
        return $this->hasOption($name) ? $this->options[$name] : $defaultValue;
    }

    /**
     * @return string
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param string $callback
     *
     * @return Route
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return Route
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function isControllerActionCallback()
    {
        return !is_null($this->getCallbackControllerName());
    }

    /**
     * @return string|null
     */
    public function getCallbackControllerName()
    {
        return $this->callbackData[self::CALLBACK_KEY_CONTROLLER];
    }

    /**
     * @return string|null
     */
    public function getCallbackActionName()
    {
        return $this->callbackData[self::CALLBACK_KEY_ACTION];
    }

    /**
     * @return string|null
     */
    public function getCallbackApplicationMethodName()
    {
        return $this->callbackData[self::CALLBACK_KEY_METHOD];
    }


}