<?php


namespace H4D\Leveret\Service\Types;


class ResourceService extends AbstractService
{
    /**
     * @var resource
     */
    protected $value;

    /**
     * ResourceService constructor.
     *
     * @param string $name
     * @param resource $resource
     * @param array $options
     */
    public function __construct($name, $resource, array $options = [])
    {
        if (!is_object($resource))
        {
            throw  new \InvalidArgumentException('Invalid param $resource!. ' .
                                                  'Param $resource must be a resource.');
        }
        parent::__construct($name, $resource, $options);
    }


    /**
     * @return resource
     */
    public function getValue()
    {
        return $this->value;
    }
}