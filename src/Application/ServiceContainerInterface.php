<?php


namespace H4D\Leveret\Application;


interface ServiceContainerInterface
{
    /**
     * @param string $serviceName
     * @param mixed $service
     * @param bool $singleton
     *
     * @return void
     */
    public function register($serviceName, $service, $singleton = false);

    /**
     * @param string $serviceName
     *
     * @return bool
     */
    public function isRegistered($serviceName);

    /**
     * @param string $serviceName
     *
     * @return mixed
     */
    public function get($serviceName);

    /**
     * @return void
     */
    public function reset();

    /**
     * @param string $serviceName
     *
     * @return void
     */
    public function remove($serviceName);
}