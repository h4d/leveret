<?php

namespace H4D\Leveret\Validation\Adapters;


use H4D\Leveret\Validation\ConstraintInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

class SymfonyConstraintAdapter implements ConstraintInterface
{
    /**
     * @var Constraint
     */
    protected $symfonyContraint;
    /**
     * @var ValidatorInterface
     */
    protected $symfonyValidaror;
    /**
     * @var ConstraintViolationList
     */
    protected $symfonyViolations;

    /**
     * @param Constraint $constraint
     */
    public function __construct(Constraint $constraint)
    {
        $this->symfonyContraint = $constraint;
        $this->symfonyValidaror = (new ValidatorBuilder())->getValidator();
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        $violations = [];
        foreach($this->symfonyViolations as $violation)
        {
            $violations[] = $violation->getMessage();
        }

        return $violations;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value)
    {
        $this->symfonyViolations = $this->symfonyValidaror->validate($value, $this->symfonyContraint);

        return (count($this->symfonyViolations) == 0);
    }
}