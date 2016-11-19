<?php


namespace Application;


use H4D\Leveret\Application\View;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    public function test_construct_returnsViewInstance()
    {
        $view = new View();
        $this->assertTrue($view instanceof View);
    }

    public function test_translate_withNoTranslator_returnsSameString()
    {
        $string = 'Hello world!';
        $view = new View();
        $translated = $view->translate($string);
        $this->assertTrue(is_string($translated));
        $this->assertEquals($string, $translated);
    }

    public function test_escapeHtml_escapesHtmlProperly()
    {
        $htmlString = '<h1>Hello world!</h1>';
        $spectedString = '&lt;h1&gt;Hello world!&lt;/h1&gt;';
        $view = new View();
        $escapedString = $view->escapeHtml($htmlString);
        $this->assertEquals($spectedString, $escapedString);
    }

    public function test_formatDate_withDateTime_formatsDateProperly()
    {
        date_default_timezone_set('UTC');
        $date = new \DateTime('2015-08-18T12:30:59');
        $view = new View();

        $formatedDate = $view->formatDate($date, 'date', 'es_ES');
        $this->assertEquals('18/08/2015', $formatedDate);
        $formatedDate = $view->formatDate($date, 'time', 'es_ES');
        $this->assertEquals('12:30:59 (UTC)', $formatedDate);
        $formatedDate = $view->formatDate($date, 'shortTime', 'es_ES');
        $this->assertEquals('12:30 (UTC)', $formatedDate);
        $formatedDate = $view->formatDate($date, 'dateTime', 'es_ES');
        $this->assertEquals('18/08/2015 12:30:59 (UTC)', $formatedDate);

        $formatedDate = $view->formatDate($date, 'date', 'en_GB');
        $this->assertEquals('2015-08-18', $formatedDate);
        $formatedDate = $view->formatDate($date, 'time', 'en_GB');
        $this->assertEquals('12:30:59 (UTC)', $formatedDate);
        $formatedDate = $view->formatDate($date, 'shortTime', 'en_GB');
        $this->assertEquals('12:30 (UTC)', $formatedDate);
        $formatedDate = $view->formatDate($date, 'dateTime', 'en_GB');
        $this->assertEquals('2015-08-18 12:30:59 (UTC)', $formatedDate);

    }
}