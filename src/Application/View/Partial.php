<?php


namespace H4D\Leveret\Application\View;

use H4D\Leveret\Application\View;
use H4D\Template\Template;

class Partial extends Template
{

    /**
     * @var array
     */
    protected $requiredConstructorOptions = ['view'];
    /**
     * @var View
     */
    protected $view;

    /**
     * Partial constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        $this->setView($options['view']);
    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param View $view
     *
     * @return Partial
     */
    public function setView(View $view)
    {
        $this->view = $view;

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
        return call_user_func_array([$this->getView(), $name], $arguments);
    }

}