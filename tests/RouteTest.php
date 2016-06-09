<?php

require_once __DIR__.'/Mocks/SampleAuth.php';
require_once __DIR__.'/Mocks/SampleConstraint.php';

use H4D\Leveret\Application\Route;

class RouteTest extends PHPUnit_Framework_TestCase
{

    /**
     * @return string
     */
    public function getPattern()
    {
        return '/test/route/:(string)var1/:(int)var2/:(bool)var3';
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return Route::create($this->getPattern());
    }

    public function test_create_returnsRouteInstance()
    {
        $route = Route::create($this->getPattern());
        $this->assertTrue($route instanceof Route);
    }

    /**
     * @depends test_create_returnsRouteInstance
     */
    public function test_controllerSetterAndGetter_worksProperly()
    {
        $controllerClass = 'MyControllerClass';
        $actionName = 'myActionName';
        $route = $this->getRoute();
        $this->assertNull($route->getControllerActionName());
        $this->assertNull($route->getControllerClassName());

        $route->useController($controllerClass, $actionName);
        $this->assertEquals($controllerClass, $route->getControllerClassName());
        $this->assertEquals($actionName, $route->getControllerActionName());
    }

    /**
     * @depends test_controllerSetterAndGetter_worksProperly
     */
    public function test_hasController_returnsProperValues()
    {
        $route = $this->getRoute();
        $this->assertFalse($route->hasController());
        $route->useController('MyControllerClass', 'myActionName');
        $this->assertTrue($route->hasController());
    }

    /**
     * @depends test_create_returnsRouteInstance
     */
    public function test_requiredParamsGettersAdnSetters_worksProperly()
    {
        $route = $this->getRoute();
        $this->assertEquals([], $route->getRequiredParams());
        $route->setRequiredParam('name');
        $this->assertEquals(['name'], $route->getRequiredParams());
        $route->setRequiredParams(['a'=>'A', 'b'=>'B']);
        $this->assertEquals(['a'=>'A', 'b'=>'B'], $route->getRequiredParams());
    }

    /**
     * @depends test_requiredParamsGettersAdnSetters_worksProperly
     */
    public function test_hasRequiredParams_returnsProperValues()
    {
        $route = $this->getRoute();
        $route->hasRequiredParams();
    }

    /**
     * @depends test_create_returnsRouteInstance
     */
    public function test_authRequiredAndGetAuthenticator_worksProperly()
    {
        $auth = new \Mocks\SampleAuth();
        $route = $this->getRoute();
        $route->authRequired($auth);
        $this->assertEquals($auth, $route->getAuthenticator());
    }

    /**
     * @depends test_authRequiredAndGetAuthenticator_worksProperly
     */
    public function test_hasAuthRequirements_returnsProperValues()
    {
        $auth = new \Mocks\SampleAuth();
        $route = $this->getRoute();
        $this->assertFalse($route->hasAuthRequirements());
        $route->authRequired($auth);
        $this->assertTrue($route->hasAuthRequirements());
    }

    /**
     * @depends test_create_returnsRouteInstance
     */
    public function test_addPostDispatchActionAndGetPostDispatchActions_worksProperly()
    {
        $callback = function (){};
        $route = $this->getRoute();
        $this->assertEquals([], $route->getPostDispatchActions());
        $route->addPostDispatchAction($callback);
        $this->assertEquals([$callback], $route->getPostDispatchActions());
    }

    /**
     * @depends test_addPostDispatchActionAndGetPostDispatchActions_worksProperly
     */
    public function test_hasPostDispatchActions_returnsProperValues()
    {
        $route = $this->getRoute();
        $this->assertFalse($route->hasPostDispatchActions());
        $route->addPostDispatchAction(function(){});
        $this->assertTrue($route->hasPostDispatchActions());
    }

    /**
     * @depends test_create_returnsRouteInstance
     */
    public function test_addPreDispatchActionAndGetPreDispatchActions_worksProperly()
    {
        $callback = function (){};
        $route = $this->getRoute();
        $this->assertEquals([], $route->getPreDispatchActions());
        $route->addPreDispatchAction($callback);
        $this->assertEquals([$callback], $route->getPreDispatchActions());
    }

    /**
     * @depends test_addPreDispatchActionAndGetPreDispatchActions_worksProperly
     */
    public function test_hasPreDispatchActions_returnsProperValues()
    {
        $route = $this->getRoute();
        $this->assertFalse($route->hasPreDispatchActions());
        $route->addPreDispatchAction(function(){});
        $this->assertTrue($route->hasPreDispatchActions());
    }

    public function test_getRegex_returnsProperValue()
    {
        $intRegex = '([-+]?[0-9]*)';
        $numberRegex = '([-+]?[0-9]*[.]?[0-9]+)';
        $wordRegex = '([\w]*)';
        $stringRegex = '([^\/\n\r]+)';

        $route = Route::create('/:(int)var');
        $this->assertEquals('\/' . $intRegex, $route->getRegex());

        $route = Route::create('/:(float)var');
        $this->assertEquals('\/' . $numberRegex, $route->getRegex());

        $route = Route::create('/:(number)var');
        $this->assertEquals('\/' . $numberRegex, $route->getRegex());

        $route = Route::create('/:(word)var');
        $this->assertEquals('\/' . $wordRegex, $route->getRegex());

        $route = Route::create('/:(string)var');
        $this->assertEquals('\/' . $stringRegex, $route->getRegex());

        $route = Route::create('/:var');
        $this->assertEquals('\/' . $stringRegex, $route->getRegex());
    }

    /**
     * @depends test_create_returnsRouteInstance
     */
    public function test_defaultFilterGetterAndSetter_worksProperly()
    {
        $route = $this->getRoute();
        $this->assertNull($route->getDefaultFilter());
        $filter = new \H4D\Leveret\Filter\Filters\DefaultFilter();
        $route->setDefaultFilter($filter);
        $this->assertEquals($filter, $route->getDefaultFilter());
    }

    /**
     * @depends test_defaultFilterGetterAndSetter_worksProperly
     */
    public function test_paramsSetterAndGetters_worksProperly()
    {
        $route = Route::create('/test/params/:var1/:var2');
        $route->setDefaultFilter(new \H4D\Leveret\Filter\Filters\DefaultFilter());
        $this->assertEquals([], $route->getParams());
        $route->setParams(['value1', 'value2']);
        $this->assertEquals([2 => 'value1', 3 => 'value2'], $route->getParams());
        $this->assertEquals(['var1' => 'value1', 'var2' => 'value2'], $route->getNamedParams());
        $this->assertEquals('value1', $route->getParamByName('var1'));
        $this->assertEquals('value2', $route->getParamByName('var2'));
        $this->assertEquals('value1', $route->getParamByPathPosition(2));
        $this->assertEquals('value2', $route->getParamByPathPosition(3));
    }

    /**
     * @depends test_create_returnsRouteInstance
     */
    public function test_nameSetterAndGetter_worksProperly()
    {
        $name = 'Test';
        $route = $this->getRoute();
        $this->assertEquals('UnnamedRoute', $route->getName());
        $route->setName($name);
        $this->assertEquals($name, $route->getName());
    }

    /**
     * @depends test_create_returnsRouteInstance
     */
    public function test_requestConstraintsAddAndGet_worksProperly()
    {
        $r = $this->getRoute();
        $this->assertEquals([], $r->getRequestConstraints());
        $constraints = [new \Mocks\SampleConstraint()];
        $r->addRequestConstraints('var1', $constraints);
        $this->assertEquals(['var1' => $constraints], $r->getRequestConstraints());
    }

    /**
     * @depends test_requestConstraintsAddAndGet_worksProperly
     */
    public function test_hasRequestConstraints_returnsProperValues()
    {
        $r = $this->getRoute();
        $this->assertFalse($r->hasRequestConstraints());
        $constraints = [new \Mocks\SampleConstraint()];
        $r->addRequestConstraints('var1', $constraints);
        $this->assertTrue($r->hasRequestConstraints());
    }

    /**
     * @depends test_create_returnsRouteInstance
     */
    public function test_requestFilterAddAndGet_worksProperly()
    {
        $r = $this->getRoute();
        $this->assertEquals([], $r->getRequestFilters());
        $filters = [function(){}];
        $r->addRequestFilters('var1', $filters);
        $this->assertEquals(['var1' => $filters], $r->getRequestFilters());
    }

    /**
     * @depends test_requestFilterAddAndGet_worksProperly
     */
    public function test_hasRequestFilters_returnsProperValues()
    {
        $r = $this->getRoute();
        $this->assertFalse($r->hasRequestFilters());
        $filters = [function(){}];
        $r->addRequestFilters('var1', $filters);
        $this->assertTrue($r->hasRequestFilters());
    }
}