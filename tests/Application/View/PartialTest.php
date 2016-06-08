<?php


namespace Application\View;

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
        $partial = new Partial(['view'=>$view]);
        $input = '<h1>hello</h1>';
        // Call view method via partial's magic __call
        /** @noinspection PhpUndefinedMethodInspection */
        $escaped = $partial->escapeHtml($input);
        $this->assertEquals(htmlspecialchars($input), $escaped);
    }
}