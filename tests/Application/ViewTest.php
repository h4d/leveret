<?php


namespace H4D\Leveret\Tests\Unit\Application;


use H4D\I18n\DateDecorator;
use H4D\Leveret\Application\View;
use H4D\Leveret\Tests\Unit\PrivateAccessTrait;
use H4D\Patterns\Collections\ArrayCollection;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    use PrivateAccessTrait;

    public function test_construct_returnsViewInstance()
    {
        $view = new View();
        $this->assertTrue($view instanceof View);
    }

    public function test_registerHelper_storesNewHelperProperly()
    {
        $view = new View();
        $helper = new View\Helpers\EscapeHelper('escape');
        $view->registerHelper($helper);
        /** @var ArrayCollection $helpersCollection */
        $helpersCollection = $this->getNonPublicPropertyValue($view, 'helpersCollection');
        $this->assertTrue($helpersCollection->has('escape'));
        $this->assertEquals($helper, $helpersCollection->get('escape'));
    }

    /**
     * @depends test_registerHelper_storesNewHelperProperly
     */
    public function test_getHelper_worksProperly()
    {
        $view = new View();
        $helper = new View\Helpers\EscapeHelper('escape');
        $view->registerHelper($helper);

        $this->assertEquals($helper, $view->getHelper('escape'));
    }

    /**
     * @depends test_registerHelper_storesNewHelperProperly
     */
    public function test_magicMethods_workProperly()
    {
        date_default_timezone_set('UTC');
        $view = new View();

        $helper = new View\Helpers\EscapeHelper('escape');
        $view->registerHelper($helper);

        $helper = new View\Helpers\DateDecorationHelper('dateDecorator', new DateDecorator());
        $view->registerHelper($helper);

        $testTest = 'test';
        /** @noinspection PhpUndefinedMethodInspection */
        $result = $view->escape($testTest);
        $this->assertEquals($result, $testTest);

        $dateString = '2010-01-01';
        $date = new \DateTime($dateString);
        /** @noinspection PhpUndefinedFieldInspection */
        $result = $view->dateDecorator->date($date);
        $this->assertEquals($dateString, $result);
    }
}