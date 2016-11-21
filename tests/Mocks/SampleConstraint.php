<?php


namespace H4D\Leveret\Tests\Unit\Mocks;


use H4D\Leveret\Validation\ConstraintInterface;

class SampleConstraint implements ConstraintInterface
{

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value)
    {
        return true;
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        return [];
    }
}