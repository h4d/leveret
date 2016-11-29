<?php


namespace H4D\Leveret\Application\View;


use H4D\Leveret\Application\View;

trait ViewAwareTrait
{
    /**
     * @var View
     */
    protected $view;

    /**
     * @param View $view
     */
    public function setView(View $view)
    {
        $this->view = $view;
    }
}