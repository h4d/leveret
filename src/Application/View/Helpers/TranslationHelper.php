<?php


namespace H4D\Leveret\Application\View\Helpers;


use H4D\I18n\TranslatorInterface;

class TranslationHelper extends AbstractHelper
{
    /**
     * @var TranslatorInterface
     */
    protected $tranlator;

    /**
     * TranslatorHelper constructor.
     *
     * @param string $alias
     * @param TranslatorInterface $translator
     */
    public function __construct($alias, TranslatorInterface $translator)
    {
        $this->alias = $alias;
        $this->tranlator = $translator;
    }

    /**
     * This function supports extra params for var substition in $string.
     *
     * @param string $string
     *
     * @return string
     */
    public function __invoke($string)
    {
        return call_user_func_array([$this->tranlator, 'translate'], func_get_args());
    }

}