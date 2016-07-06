<?php


namespace H4D\Leveret\Service\Types;


class InstanceService extends AbstractService
{
    /**
     * @var object
     */
    protected $value;

    /**
     * ObjectInstanceService constructor.
     *
     * @param string $name
     * @param object $instance
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $instance, array $options = [])
    {
        if (!is_object($instance))
        {
            throw  new \InvalidArgumentException('Invalid param $instance!. ' .
                                                 'Param $instance must be an object.');
        }
        parent::__construct($name, $instance, $options);
    }


    /**
     * @return object
     */
    public function getValue()
    {
        return $this->value;
    }
}