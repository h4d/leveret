<?php


namespace H4D\Leveret\Tests\Unit\Application\View\Helpers;


use H4D\Leveret\Application\View\Helpers\EscapeHelper;

class EscapeHelperTest extends \PHPUnit_Framework_TestCase
{
    public function test_invoke_worksProperly()
    {
        $htmlString = '<h1>Hello world!</h1>';
        $spectedString = '&lt;h1&gt;Hello world!&lt;/h1&gt;';
        $helper = new EscapeHelper('escape');
        $escapedString = $helper($htmlString);

        $this->assertEquals($spectedString, $escapedString);

    }

}