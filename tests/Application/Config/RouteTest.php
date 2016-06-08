<?php


namespace Application\Config;


use H4D\Leveret\Application\Config\Route;
use H4D\Leveret\Http\Request;

class RouteTest extends \PHPUnit_Framework_TestCase
{

    public function getRoute()
    {
        $params = [];
        $params[Route::KEY_PATTERN] = '/test/route/:param';
        $params[Route::KEY_METHOD] = Request::METHOD_GET;
        $params[Route::KEY_CALLBACK] = 'MyController::MyAction';
        $params[Route::KEY_OPTIONS] = ['option-1'=>'A', 'option-2'=>'B'];

        return Route::create($params);
    }
    /**
     * @expectedException \H4D\Leveret\Exception\ConfigRouteException
     */
    public function test_create_withMissingKeys_throwsException()
    {
        $params = [];
        Route::create($params);
    }

    /**
     * @throws \H4D\Leveret\Exception\ConfigRouteException
     */
    public function test_create_withProperKeys_returnsRoute()
    {
        $route = $this->getRoute();
        $this->assertTrue($route instanceof Route);
    }

    public function test_getters_returnProperValues()
    {
        $params = [];
        $params[Route::KEY_PATTERN] = '/test/route/:param';
        $params[Route::KEY_METHOD] = Request::METHOD_GET;
        $params[Route::KEY_CALLBACK] = 'MyController::MyAction';
        $params[Route::KEY_OPTIONS] = ['option-1'=>'A', 'option-2'=>'B'];
        $route =  Route::create($params);
        $this->assertEquals($params[Route::KEY_PATTERN], $route->getPattern());
        $this->assertEquals($params[Route::KEY_METHOD], $route->getMethod());
        $this->assertEquals($params[Route::KEY_CALLBACK], $route->getCallback());
        $this->assertEquals($params[Route::KEY_OPTIONS], $route->getOptions());
        $this->assertEquals('A', $route->getOption('option-1'));
        $this->assertEquals('B', $route->getOption('option-2'));
        $this->assertEquals('nope', $route->getOption('option-3', 'nope'));
    }

    public function test_getCallbackControllerName_restunsProperControllerName()
    {
        $route = $this->getRoute();
        $this->assertEquals('MyController', $route->getCallbackControllerName());
    }

    public function test_getCallback_restunsProperValue()
    {
        $route = $this->getRoute();
        $this->assertEquals('MyController::MyAction', $route->getCallback());
        $route->setCallback('MyMethod');
        $this->assertEquals('MyMethod', $route->getCallback());
    }

    public function test_getCallbackActionName_restunsProperActionName()
    {
        $route = $this->getRoute();
        $this->assertEquals('MyAction', $route->getCallbackActionName());
    }

    public function test_getCallbackApplicationMethodName_restunsProperMethodName()
    {
        $params = [];
        $params[Route::KEY_PATTERN] = '/test/route/:param';
        $params[Route::KEY_METHOD] = Request::METHOD_GET;
        $params[Route::KEY_CALLBACK] = 'MyMethod';
        $route = Route::create($params);
        $this->assertEquals('MyMethod', $route->getCallbackApplicationMethodName());
    }

    public function test_getCallbackApplicationMethodNameAfterSettingCallback_restunsProperMethodName()
    {
        $route = $this->getRoute();
        $route->setCallback('MyMethod');
        $this->assertEquals('MyMethod', $route->getCallbackApplicationMethodName());
    }

    public function test_isControllerActionCallback_returnsProperValue()
    {
        $route = $this->getRoute();
        $this->assertTrue($route->isControllerActionCallback());
        $route->setCallback('MyMethod');
        $this->assertFalse($route->isControllerActionCallback());
    }

    public function test_setters_setValuesProperly()
    {
        $route = $this->getRoute();

        $newCallback = 'NewCallback';
        $route->setCallback($newCallback);
        $this->assertEquals($newCallback, $route->getCallback());

        $newMethod = Request::METHOD_POST;
        $route->setMethod($newMethod);
        $this->assertEquals($newMethod, $route->getMethod());

        $newOptions = ['a'=>'A'];
        $route->setOptions($newOptions);
        $this->assertEquals($newOptions, $route->getOptions());

        $newPattern = '/';
        $route->setPattern($newPattern);
        $this->assertEquals($newPattern, $route->getPattern());
    }


    
}