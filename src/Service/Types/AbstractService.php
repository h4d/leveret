<?php


namespace H4D\Leveret\Service\Types;

use H4D\Leveret\Service\ServiceInterface;

abstract class AbstractService implements ServiceInterface
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var null|bool|int|float|string|array|resource|object|callable
     */
    protected $value;
    /**
     * @var array
     */
    protected $options = [];
    /**
     * @return mixed
     */
    abstract public function getValue();

    /**
     * AbstractService constructor.
     *
     * @param string $name
     * @param null|bool|int|float|string|array|resource|object|callable $callable
     * @param array $options
     */
    public function __construct($name, $callable, $options = [])
    {
        $this->name = $name;
        $this->value = $callable;
        $this->options = $options;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasOption($key)
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function getOption($key, $default = null)
    {
        return $this->hasOption($key) ? $this->options[$key] : $default;
    }

}