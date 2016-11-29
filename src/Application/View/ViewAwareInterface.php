<?php


namespace H4D\Leveret\Application\View;


use H4D\Leveret\Application\View;

interface ViewAwareInterface
{
    /**
     * @param View $view
     *
     * @return void
     */
    public function setView(View $view);
}