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
     * @param \DateTime|string $date
     * @param string $formatAlias
     * @param string $locale
     *
     * @return string
     */
    public function formatDateTime($date, $formatAlias, $locale = '')
    {
        $date = (is_string($date)) ? new \DateTime($date) : $date;
        return $this->dateDecorator->getFormattedDate($date, $formatAlias, $locale);
    }

    /**
     * @param \DateTime|string $date
     * @param string $locale
     *
     * @return string
     */
    public function date($date, $locale = '')
    {
        $date = (is_string($date)) ? new \DateTime($date) : $date;
        return $this->dateDecorator->getDate($date, $locale);
    }

    /**
     * @param \DateTime|string $date
     * @param string $locale
     *
     * @return string
     */
    public function dateTime($date, $locale = '')
    {
        $date = (is_string($date)) ? new \DateTime($date) : $date;
        return $this->dateDecorator->getDateTime($date, $locale);
    }

    /**
     * @param \DateTime|string $date
     * @param string $locale
     *
     * @return string
     */
    public function timestamp($date, $locale = '')
    {
        $date = (is_string($date)) ? new \DateTime($date) : $date;
        return $this->dateDecorator->getTimestamp($date, $locale);
    }

    /**
     * @param \DateTime|string $date
     * @param string $locale
     *
     * @return string
     */
    public function time($date, $locale = '')
    {
        $date = (is_string($date)) ? new \DateTime($date) : $date;
        return $this->dateDecorator->getTime($date, $locale);
    }

    /**
     * @param \DateTime|string $date
     * @param string $locale
     *
     * @return mixed
     */
    public function timeShort($date, $locale = '')
    {
        $date = (is_string($date)) ? new \DateTime($date) : $date;
        return $this->dateDecorator->getShortTime($date, $locale);
    }
}