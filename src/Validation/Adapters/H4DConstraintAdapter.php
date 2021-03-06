<?php

namespace H4D\Leveret\Validation\Adapters;


use H4D\Leveret\Validation\ConstraintInterface;
use H4D\Validator\Constraint;
use H4D\Validator\ConstraintViolation;

class H4DConstraintAdapter implements ConstraintInterface
{
    /**
     * @var Constraint
     */
    protected $h4dContraint;

    /**
     * @param Constraint $constraint
     */
    public function __construct(Constraint $constraint)
    {
        $this->h4dContraint = $constraint;
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        $message = 'Undefined violation!';
        $violation = $this->h4dContraint->getViolation();
        if ($violation instanceof ConstraintViolation)
        {
            $message = $violation->getMessage();
        }
        return [$message];
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value)
    {
        return $this->h4dContraint->validate($value);
    }
}