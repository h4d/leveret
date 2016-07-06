<?php


namespace H4D\Leveret\Service\Types;


class KeyValueService extends AbstractService
{
    /**
     * @var null|bool|int|float|string|array
     */
    protected $value;

    public function __construct($name, $value, array $options = [])
    {
        if (is_object($value))
        {
            throw  new \InvalidArgumentException('Invalid param $value!. ' .
                                                  'Use CallableService for objects.');
        }
        if (is_callable($value))
        {
            throw  new \InvalidArgumentException('Invalid param $value!. ' .
                                                  'Use CallableService for callbacks.');
        }
        if (is_resource($value))
        {
            throw  new \InvalidArgumentException('Invalid param $value!. ' .
                                                  'Use ResourceService for resources.');
        }
        parent::__construct($name, $value, $options);
    }


    /**
     * @return null|bool|int|float|string|array
     */
    public function getValue()
    {
        return $this->value;
    }
}