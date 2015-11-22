<?php


namespace H4D\Leveret\Filter\Filters;

use H4D\Leveret\Filter\FilterInterface;

class DefaultFilter implements FilterInterface
{
    /**
     * @var int
     */
    protected $filterType;
    /**
     * @var array|null
     */
    protected $options;

    /**
     * DefaultFilter constructor.
     *
     * @param int $filterType
     * @param null $options
     */
    public function __construct($filterType = FILTER_UNSAFE_RAW, $options = null)
    {
        $this->filterType = $filterType;
        $this->options = $options;
    }

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
            $filtered = filter_var($value, $this->filterType, $this->options);
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