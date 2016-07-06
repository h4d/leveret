<?php


namespace H4D\Leveret\Service;

use H4D\Leveret\Application\ServiceContainerInterface;
use H4D\Leveret\Exception\ServiceContainerException;
use H4D\Leveret\Service\Types\CallableService;
use H4D\Leveret\Service\Types\KeyValueService;
use H4D\Leveret\Service\Types\InstanceService;
use H4D\Leveret\Service\Types\ResourceService;
use H4D\Patterns\Traits\SingletonTrait;

class ServiceContainer implements ServiceContainerInterface
{
    use SingletonTrait;

    /**
     * @var ServiceInterface[]
     */
    protected $services = [];

    /**
     * @param string $serviceName
     * @param mixed $value
     * @param bool $singleton Call only one time if $value is a callable.
     *
     * @throws ServiceContainerException
     */
    public function register($serviceName, $value, $singleton = false)
    {
        if (is_callable($value))
        {
            $this->registerCallable($serviceName, $value, $singleton);
        }
        elseif (is_object($value))
        {
            $this->registerInstance($serviceName, $value);
        }
        elseif (is_resource($value))
        {
            $this->registerResource($serviceName, $value);
        }
        else
        {
            $this->registerValue($serviceName, $value);
        }
    }

    /**
     * @param string $serviceName
     * @param null|bool|int|float|string|array $value
     *
     * @return $this
     * @throws ServiceContainerException
     */
    public function registerValue($serviceName, $value)
    {
        if (is_object($value))
        {
            throw  new ServiceContainerException('Invalid param $value!. ' .
                                                 'Use registerInstance method to register objects.');
        }
        if (is_callable($value))
        {
            throw  new ServiceContainerException('Invalid param $value!. ' .
                                                 'Use registerCallable method to register callbacks.');
        }
        if (is_resource($value))
        {
            throw  new ServiceContainerException('Invalid param $value!. ' .
                                                 'Use registerResource method to register resources.');
        }
        $this->services[$serviceName] = new KeyValueService($serviceName, $value);
    }

    /**
     * @param string $serviceName
     * @param object $instance
     *
     * @throws ServiceContainerException
     */
    public function registerInstance($serviceName, $instance)
    {
        if (!is_object($instance))
        {
            throw  new ServiceContainerException('Invalid param $instance!. ' .
                                                 'Param $instance must be an object.');
        }
        $this->services[$serviceName] = new InstanceService($serviceName, $instance);
    }


    /**
     * @param string $serviceName
     * @param resource $resource
     *
     * @throws ServiceContainerException
     */
    public function registerResource($serviceName, $resource)
    {
        if (!is_object($resource))
        {
            throw  new ServiceContainerException('Invalid param $resource!. ' .
                                                 'Param $resource must be a resource.');
        }
        $this->services[$serviceName] = new ResourceService($serviceName, $resource);
    }

    /**
     * @param string $serviceName
     * @param callable $callable
     * @param bool $singleton
     *
     * @throws ServiceContainerException
     */
    public function registerCallable($serviceName, $callable, $singleton = false)
    {
        if (!is_callable($callable))
        {
            throw  new ServiceContainerException('Invalid param $callable!. ' .
                                                 'Param $callable must be callable.');
        }
        $this->services[$serviceName] = new CallableService($serviceName,
                                                            $callable,
                                                            [ServiceInterface::OPTION_CALLABLE_SINGLETON => $singleton]);
    }

    /**
     * @param string $serviceName
     *
     * @return bool
     */
    public function isRegistered($serviceName)
    {
        return (array_key_exists($serviceName, $this->services));
    }

    /**
     * @param string $serviceName
     *
     * @return mixed
     * @throws ServiceContainerException
     */
    public function get($serviceName)
    {
        if (!$this->isRegistered($serviceName))
        {
            throw new ServiceContainerException(sprintf('Service "%s" is not registered!',
                                                        $serviceName));
        }
        /** @var ServiceInterface $service */
        $service = $this->services[$serviceName];

        return $service->getValue();
    }

    /**
     * @param string $serviceName
     *
     * @return $this
     */
    public function remove($serviceName)
    {
        if ($this->isRegistered($serviceName))
        {
            unset($this->services[$serviceName]);
        }

        return $this;
    }


    /**
     * @return ServiceInterface[]
     */
    public function getRegisteredServices()
    {
        return $this->services;
    }

    /**
     * @return array
     */
    public function getRegisteredServicesNames()
    {
        return array_keys($this->services);
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->services = [];

        return $this;
    }
}