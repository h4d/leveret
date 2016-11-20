<?php


namespace H4D\Leveret\Application\View\Helpers;


class EscapeHelper extends AbstractHelper
{
    /**
     * EscapeHelper constructor.
     *
     * @param string $alias
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function __invoke($string)
    {
        return htmlspecialchars($string);
    }
}