<?php


namespace H4D\Leveret\Filter;


interface FilterInterface
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function filter($value);
}