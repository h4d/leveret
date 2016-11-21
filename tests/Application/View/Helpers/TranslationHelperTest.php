<?php


namespace H4D\Leveret\Tests\Unit\Application\View\Helpers;


use H4D\I18n\NullTranslator;
use H4D\Leveret\Application\View\Helpers\TranslationHelper;

class TranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    public function test_invoke_worksProperly()
    {
        $string = 'Hello world!';
        $helper = new TranslationHelper('trans', new NullTranslator());
        $translated = $helper($string);
        $this->assertTrue(is_string($translated));
        $this->assertEquals($string, $translated);
    }
}