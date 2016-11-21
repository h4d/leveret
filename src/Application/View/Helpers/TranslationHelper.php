<?php


namespace H4D\Leveret\Application\View\Helpers;


use H4D\I18n\TranslatorInterface;

class TranslationHelper extends AbstractHelper
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * TranslatorHelper constructor.
     *
     * @param string $alias
     * @param TranslatorInterface $translator
     */
    public function __construct($alias, TranslatorInterface $translator)
    {
        $this->alias = $alias;
        $this->translator = $translator;
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
        return call_user_func_array([$this->translator, 'translate'], func_get_args());
    }

}