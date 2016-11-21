<?php


namespace H4D\Leveret\Application\View\Helpers;


use H4D\I18n\DateDecoratorInterface;

class DateDecorationHelper extends AbstractHelper
{
    /**
     * @var DateDecoratorInterface
     */
    protected $dateDecorator;

    /**
     * DateDecoratorHelper constructor.
     *
     * @param string $alias
     * @param DateDecoratorInterface $dateDecorator
     */
    public function __construct($alias, DateDecoratorInterface $dateDecorator)
    {
        $this->alias = $alias;
        $this->dateDecorator = $dateDecorator;
    }

    /**
     * @return $this
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * @param \DateTime|string $date
     * @param string $formatAlias
     * @param string $locale
     *
     * @return string
     */
    public function formatDate($date, $formatAlias, $locale = '')
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
     * @return string
     */
    public function timeShort($date, $locale = '')
    {
        $date = (is_string($date)) ? new \DateTime($date) : $date;
        return $this->dateDecorator->getTimeShort($date, $locale);
    }

}