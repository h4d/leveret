<?php

namespace H4D\Leveret\Application;

use H4D\I18n\NullTranslator;
use H4D\I18n\TranslatorAwareTrait;
use H4D\Template\TemplateTrait;
use H4D\Leveret\Application\View\Partial;

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

    /**
     * @param string $string
     *
     * @return string
     */
    public function escapeHtml($string)
    {
        return htmlspecialchars($string);
    }

    /**
     * @param string $route
     * @param array $vars
     *
     * @return Partial
     */
    public function partial($route, array $vars = [])
    {
        $partial = new Partial(['view' => $this]);
        $partial->setTemplateFile($route);
        // Add main view vars
        $partial->addVars($this->getVars());
        // Add local vars (can overide main view vars)
        if (count($vars) > 0)
        {
            $partial->addVars($vars);
        }

        return $partial;
    }
}