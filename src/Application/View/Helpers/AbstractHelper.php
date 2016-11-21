<?php


namespace H4D\Leveret\Application\View\Helpers;


use H4D\Leveret\Application\View;

abstract class AbstractHelper
{
    /**
     * @var string
     */
    protected $alias;

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

}