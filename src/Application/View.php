<?php

namespace H4D\Leveret\Application;

use H4D\I18n\DateDecorator;
use H4D\I18n\DateDecoratorAwareTrait;
use H4D\I18n\NullTranslator;
use H4D\I18n\TranslatorAwareTrait;
use H4D\Template\TemplateTrait;
use H4D\Leveret\Application\View\Partial;

class View
{

    use TemplateTrait;
    use TranslatorAwareTrait;
    use DateDecoratorAwareTrait;

    public function __construct()
    {
        $this->translator = new NullTranslator();
        $this->dateDecorator = new DateDecorator();
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

    /**
     * @param \DateTime $date
     * @param string $formatAlias
     * @param string $locale
     *
     * @return mixed
     */
    public function formatDateTime(\DateTime $date, $formatAlias, $locale = '')
    {
        return $this->dateDecorator->getFormattedDate($date, $formatAlias, $locale);
    }

    /**
     * @param \DateTime $date
     * @param string $locale
     *
     * @return mixed
     */
    public function date(\DateTime $date, $locale = '')
    {
        return $this->dateDecorator->getDate($date, $locale);
    }

    /**
     * @param \DateTime $date
     * @param string $locale
     *
     * @return mixed
     */
    public function dateTime(\DateTime $date, $locale = '')
    {
        return $this->dateDecorator->getDateTime($date, $locale);
    }

    /**
     * @param \DateTime $date
     * @param string $locale
     *
     * @return mixed
     */
    public function timestamp(\DateTime $date, $locale = '')
    {
        return $this->dateDecorator->getTimestamp($date, $locale);
    }

    /**
     * @param \DateTime $date
     * @param string $locale
     *
     * @return mixed
     */
    public function time(\DateTime $date, $locale = '')
    {
        return $this->dateDecorator->getTime($date, $locale);
    }

    /**
     * @param \DateTime $date
     * @param string $locale
     *
     * @return mixed
     */
    public function timeShort(\DateTime $date, $locale = '')
    {
        return $this->dateDecorator->getShortTime($date, $locale);
    }
}