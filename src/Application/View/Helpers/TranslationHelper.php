<?php


namespace H4D\Leveret\Application\View\Helpers;


use H4D\I18n\Translator;

class TranslationHelper extends AbstractHelper
{
    /**
     * @var string
     */
    protected $alias;
    /**
     * @var Translator
     */
    protected $tranlator;

    /**
     * TranslatorHelper constructor.
     *
     * @param string $alias
     * @param Translator $translator
     */
    public function __construct($alias, Translator $translator)
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

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}