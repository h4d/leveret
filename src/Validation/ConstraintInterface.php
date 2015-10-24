<?php

namespace H4D\Leveret\Validation;

interface ConstraintInterface
{

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value);

    /**
     * @return array
     */
    public function getViolations();

}