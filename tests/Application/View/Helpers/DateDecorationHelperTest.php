<?php


namespace H4D\Leveret\Tests\Unit\Application\View\Helpers;


use H4D\I18n\DateDecorator;
use H4D\Leveret\Application\View\Helpers\DateDecorationHelper;

class DateDecorationHelperTest extends \PHPUnit_Framework_TestCase
{

    public function test_invoke_worksProperly()
    {
        date_default_timezone_set('UTC');
        $helper = new DateDecorationHelper('dateDecorator', new DateDecorator());
        $this->assertEquals($helper, $helper());
    }

    public function test_dateMethodsArePresentAndWorkProperly()
    {
        date_default_timezone_set('UTC');
        $date = new \DateTime('2015-08-18T12:30:59');

        $helper = new DateDecorationHelper('dateDecorator', new DateDecorator());

        $formatedDate = $helper->formatDate($date, 'date', 'es_ES');
        $this->assertEquals('18/08/2015', $formatedDate);
        $this->assertEquals('18/08/2015', $helper->date($date, 'es_ES'));

        $formatedDate = $helper->formatDate($date, 'time', 'es_ES');
        $this->assertEquals('12:30:59 (UTC)', $formatedDate);
        $this->assertEquals('12:30:59 (UTC)', $helper->time($date, 'es_ES'));

        $formatedDate = $helper->formatDate($date, 'shortTime', 'es_ES');
        $this->assertEquals('12:30 (UTC)', $formatedDate);
        $this->assertEquals('12:30 (UTC)', $helper->timeShort($date, 'es_ES'));

        $formatedDate = $helper->formatDate($date, 'dateTime', 'es_ES');
        $this->assertEquals('18/08/2015 12:30:59 (UTC)', $formatedDate);
        $this->assertEquals('18/08/2015 12:30:59 (UTC)', $helper->dateTime($date, 'es_ES'));

        $formatedDate = $helper->formatDate($date, 'timestamp', 'es_ES');
        $this->assertEquals('1439901059s', $formatedDate);
        $this->assertEquals('1439901059s', $helper->timestamp($date, 'es_ES'));
    }
}