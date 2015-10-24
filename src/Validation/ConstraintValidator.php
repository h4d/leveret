<?php

namespace H4D\Leveret\Validation;

class ConstraintValidator
{
    /**
     * @var ConstraintInterface
     */
    protected $constraint;
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param $value
     * @param ConstraintInterface $constraint
     *
     * @return bool
     */
    public function validate($value, ConstraintInterface $constraint)
    {
        $this->value = $value;
        $this->constraint = $constraint;

        return $constraint->validate($value);
    }

    /**
     * @return array
     */
    public function getConstraintViolations()
    {
        $violations = [];
        if ($this->constraint instanceof ConstraintInterface)
        {
            $violations = $this->constraint->getViolations();
        }

        return $violations;
    }

    /**
     * @return bool
     */
    public function hasConstraintViolations()
    {
        return count($this->getConstraintViolations()) > 0;
    }

}