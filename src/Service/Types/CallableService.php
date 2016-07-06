<?php


namespace H4D\Leveret\Service\Types;


class CallableService extends AbstractService
{
    /**
     * @var callable
     */
    protected $value;
    /**
     * @var bool
     */
    protected $executed = false;
    /**
     * @var mixed
     */
    protected $executionResult;

    /**
     * CallableService constructor.
     *
     * @param string $name
     * @param callable $callable
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $callable, array $options = [])
    {
        if (!is_callable($callable))
        {
            throw  new \InvalidArgumentException('Invalid param $callable!. ' .
                                                 'Param $callable must be callable.');
        }
        parent::__construct($name, $callable, $options);
    }


    /**
     * @return mixed
     */
    public function getValue()
    {
        if (!$this->executedBefore() || !$this->isSingleton())
        {
            $this->executionResult = $this->execute();
        }

        return $this->executionResult;
    }

    /**
     * @return bool
     */
    protected function isSingleton()
    {
        return (true === $this->getOption(self::OPTION_CALLABLE_SINGLETON, false));
    }

    /**
     * @return bool
     */
    protected function executedBefore()
    {
        return $this->executed;
    }

    /**
     * @return mixed
     */
    protected function execute()
    {
        $callable = $this->value;
        $result = $callable();
        $this->executed = true;

        return $result;
    }
}