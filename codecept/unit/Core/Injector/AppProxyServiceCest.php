<?php
namespace Core\Injector;
use SilverStripe\Core\Injector\AopProxyService;
use SilverStripe\Core\Tests\Injector\AopProxyServiceTest\BeforeAfterCallTestAspect;
use SilverStripe\Core\Tests\Injector\AopProxyServiceTest\ProxyTestObject;
use \UnitTester;

class AppProxyServiceCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testBeforeMethodsCalled(UnitTester $I)
    {
        $proxy = new AopProxyService();
        $aspect = new BeforeAfterCallTestAspect();
        $proxy->beforeCall = array(
            'myMethod' => $aspect
        );

        $proxy->proxied = new ProxyTestObject();

        $result = $proxy->myMethod();

        $I->assertEquals('myMethod', $aspect->called);
        $I->assertEquals(42, $result);
    }

    public function testBeforeMethodBlocks(UnitTester $I)
    {
        $proxy = new AopProxyService();
        $aspect = new BeforeAfterCallTestAspect();
        $aspect->block = true;

        $proxy->beforeCall = array(
            'myMethod' => $aspect
        );

        $proxy->proxied = new ProxyTestObject();

        $result = $proxy->myMethod();

        $I->assertEquals('myMethod', $aspect->called);

        // the actual underlying method will NOT have been called
        $I->assertNull($result);

        // set up an alternative return value
        $aspect->alternateReturn = 84;

        $result = $proxy->myMethod();

        $I->assertEquals('myMethod', $aspect->called);

        // the actual underlying method will NOT have been called,
        // instead the alternative return value
        $I->assertEquals(84, $result);
    }

    public function testAfterCall(UnitTester $I)
    {
        $proxy = new AopProxyService();
        $aspect = new BeforeAfterCallTestAspect();

        $proxy->afterCall = array(
            'myMethod' => $aspect
        );

        $proxy->proxied = new ProxyTestObject();

        $aspect->modifier = function ($value) {
            return $value * 2;
        };

        $result = $proxy->myMethod();
        $I->assertEquals(84, $result);
    }
}
