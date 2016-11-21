<?php

namespace H4D\Leveret\Application;

use H4D\Leveret\Application\View\Helpers\AbstractHelper;
use H4D\Leveret\Exception\ViewException;
use H4D\Patterns\Collections\ArrayCollection;
use H4D\Template\TemplateTrait;

class View
{
    use TemplateTrait;

    /**
     * @var ArrayCollection
     */
    protected $helpersCollection;

    public function __construct()
    {
        $this->helpersCollection = new ArrayCollection([]);
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     * @throws ViewException
     */
    public function __call($name, $arguments)
    {
        if (!$this->helpersCollection->has($name))
        {
            throw new ViewException(sprintf('View helper "%s" not found!', $name));
        }

        if (!is_callable($this->helpersCollection->get($name)))
        {
            throw new ViewException(sprintf('View helper "%s" is not callable!', $name));
        }

        return call_user_func_array($this->helpersCollection->get($name), $arguments);
    }

    /**
     * @param string $name
     *
     * @return AbstractHelper
     * @throws ViewException
     */
    public function __get($name)
    {
        return $this->getHelper($name);
    }

    /**
     * @param AbstractHelper $helper
     *
     * @return $this
     */
    public function registerHelper(AbstractHelper $helper)
    {
        if (!$this->helpersCollection->has($helper->getAlias()))
        {
            $this->helpersCollection->set($helper->getAlias(), $helper);
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return AbstractHelper
     * @throws ViewException
     */
    public function getHelper($name)
    {
        if (!$this->helpersCollection->has($name))
        {
            throw new ViewException(sprintf('View helper "%s" not found!', $name));
        }

        return $this->helpersCollection->get($name);
    }

}