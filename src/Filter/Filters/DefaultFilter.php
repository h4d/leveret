<?php


namespace H4D\Leveret\Filter\Filters;


use H4D\Leveret\Filter\FilterInterface;

class DefaultFilter implements FilterInterface
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function filter($value)
    {
        $filtered = $value;
        if (is_string($value))
        {
            $filtered = filter_var($value, FILTER_SANITIZE_STRING);
        }
        elseif(is_array($value))
        {
            foreach($value as $key=>$val)
            {
                $filtered[$key] = $this->filter($val);
            }
        }

        return $filtered;
    }
}