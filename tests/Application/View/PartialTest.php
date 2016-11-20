<?php


namespace H4D\Leveret\Tests\Unit\Application\View;

use H4D\Leveret\Application\View;
use H4D\Leveret\Application\View\Partial;

class PartialTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \H4D\Template\Exceptions\RequiredOptionMissedException
     */
    public function test_construct_withNoView_throwsException()
    {
        new Partial([]);
    }

    public function test_toString_withIncompleteView_returnString()
    {
        $view = new View();
        $partial = new Partial(['view'=>$view]);
        $string = $partial->__toString();
        // Error case.
        $this->assertEquals('Template file "" does not exists.', $string);
    }

    public function test_getterAndSetters_worksProperly()
    {
        $view = new View();
        $partial = new Partial(['view'=>$view]);

        $newView = new View();
        $newView->addTemplateVars(['a'=>'A']);
        $partial->setView($newView);
        $this->assertEquals($newView, $partial->getView());
    }

    public function test_call_worksProperly()
    {
        $view = new View();
        $view->registerHelper(new View\Helpers\EscapeHelper('escapeHtml'));
        $partial = new Partial(['view'=>$view]);
        $input = '<h1>hello</h1>';
        // Call view helper via partial's magic __call
        /** @noinspection PhpUndefinedMethodInspection */
        $escaped = $partial->escapeHtml($input);
        $this->assertEquals(htmlspecialchars($input), $escaped);
    }

    public function test_get_worksProperly()
    {
        $view = new View();
        $helper = new View\Helpers\EscapeHelper('escapeHtml');
        $view->registerHelper($helper);
        $view->addVar('testVar', 'test');
        $partial = new Partial(['view'=>$view]);
        // Get view helper via partial's magic __get
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals($helper, $partial->escapeHtml);
        $this->assertEquals($helper, $partial->getVars());
    }
}