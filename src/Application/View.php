<?php

namespace H4D\Leveret\Application;

use H4D\I18n\NullTranslator;
use H4D\I18n\TranslatorAwareTrait;
use H4D\Template\TemplateTrait;

class View
{

    use TemplateTrait;
    use TranslatorAwareTrait;

    public function __construct()
    {
        $this->translator = new NullTranslator();
    }

    /**
     * This function supports extra params for var substitution in $string.
     *
     * @param $string
     *
     * @return string
     */
    public function translate($string)
    {
        return call_user_func_array([$this->translator, 'translate'], func_get_args());
    }

}