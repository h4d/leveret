<?php


namespace H4D\Leveret\Application\View;

use H4D\Leveret\Application\View;
use H4D\Template\Template;

class Partial extends Template
{

    /**
     * @var array
     */
    protected $requiredConstructorOptions = ['parent'];
    /**
     * @var View
     */
    protected $parent;

    /**
     * Partial constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        $this->setParent($options['parent']);
    }

    /**
     * @return View
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param View $parent
     *
     * @return Partial
     */
    public function setParent(View $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->getParent()->__call($name, $arguments);
    }

    /**
     * @param string $name
     *
     * @return Helpers\AbstractHelper
     */
    public function __get($name)
    {
        return $this->getParent()->__get($name);
    }
}