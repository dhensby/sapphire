<?php
namespace Core\Injector;
use Codeception\Stub\Expected;
use Codeception\Util\Stub;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Factory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\Core\Injector\SilverStripeServiceConfigurationLocator;
use SilverStripe\Core\Tests\Injector\AopProxyServiceTest\AnotherService;
use SilverStripe\Core\Tests\Injector\AopProxyServiceTest\SampleService;
use SilverStripe\Core\Tests\Injector\InjectorTest\CircularOne;
use SilverStripe\Core\Tests\Injector\InjectorTest\CircularTwo;
use SilverStripe\Core\Tests\Injector\InjectorTest\ConstructableObject;
use SilverStripe\Core\Tests\Injector\InjectorTest\DummyRequirements;
use SilverStripe\Core\Tests\Injector\InjectorTest\InjectorTestConfigLocator;
use SilverStripe\Core\Tests\Injector\InjectorTest\MyChildClass;
use SilverStripe\Core\Tests\Injector\InjectorTest\MyParentClass;
use SilverStripe\Core\Tests\Injector\InjectorTest\NeedsBothCirculars;
use SilverStripe\Core\Tests\Injector\InjectorTest\NewRequirementsBackend;
use SilverStripe\Core\Tests\Injector\InjectorTest\OriginalRequirementsBackend;
use SilverStripe\Core\Tests\Injector\InjectorTest\OtherTestObject;
use SilverStripe\Core\Tests\Injector\InjectorTest\SSObjectCreator;
use SilverStripe\Core\Tests\Injector\InjectorTest\TestObject;
use SilverStripe\Core\Tests\Injector\InjectorTest\TestSetterInjections;
use SilverStripe\Core\Tests\Injector\InjectorTest\TestStaticInjections;
use \UnitTester;

class InjectorCest
{
    public function _before(UnitTester $I)
    {
        if (!defined('TEST_SERVICES')) {
            define('TEST_SERVICES', FRAMEWORK_PATH . '/tests/php/Core/Injector/AopProxyServiceTest');
        }
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testCorrectlyInitialised(UnitTester $I)
    {
        $injector = Injector::inst();
        $I->assertTrue(
            $injector->getConfigLocator() instanceof SilverStripeServiceConfigurationLocator,
            'Failure most likely because the injector has been referenced BEFORE being initialised in Core.php'
        );
    }

    public function testBasicInjector(UnitTester $I)
    {
        $injector = new Injector();
        $injector->setAutoScanProperties(true);
        $config = array(
            'SampleService' => array(
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class,
            )
        );

        $injector->load($config);


        $I->assertFalse($injector->has('UnknownService'));
        $I->assertNull($injector->getServiceName('UnknownService'));

        $I->assertTrue($injector->has('SampleService'));
        $I->assertEquals(
            'SampleService',
            $injector->getServiceName('SampleService')
        );

        $myObject = new TestObject();
        $injector->inject($myObject);

        $I->assertInstanceOf(
            SampleService::class,
            $myObject->sampleService
        );
    }

    public function testConfiguredInjector(UnitTester $I)
    {
        $injector = new Injector();
        $services = array(
            'AnotherService' => array(
                'class' => AnotherService::class,
                'src' => TEST_SERVICES . '/AnotherService.php',
                'properties' => array('config_property' => 'Value'),
            ),
            'SampleService' => array(
                'class' => SampleService::class,
                'src' => TEST_SERVICES . '/SampleService.php',
            )
        );

        $injector->load($services);
        $I->assertTrue($injector->has('SampleService'));
        $I->assertEquals(
            'SampleService',
            $injector->getServiceName('SampleService')
        );
        // We expect a false because the AnotherService::class is actually
        // just a replacement of the SilverStripe\Core\Tests\Injector\AopProxyServiceTest\SampleService
        $I->assertTrue($injector->has('SampleService'));
        $I->assertEquals(
            'AnotherService',
            $injector->getServiceName('AnotherService')
        );

        $item = $injector->get('AnotherService');

        $I->assertEquals('Value', $item->config_property);
    }

    public function testIdToNameMap(UnitTester $I)
    {
        $injector = new Injector();
        $services = array(
            'FirstId' => AnotherService::class,
            'SecondId' => SampleService::class,
        );

        $injector->load($services);

        $I->assertTrue($injector->has('FirstId'));
        $I->assertEquals($injector->getServiceName('FirstId'), 'FirstId');

        $I->assertTrue($injector->has('SecondId'));
        $I->assertEquals($injector->getServiceName('SecondId'), 'SecondId');

        $I->assertTrue($injector->get('FirstId') instanceof AnotherService);
        $I->assertTrue($injector->get('SecondId') instanceof SampleService);
    }

    public function testReplaceService(UnitTester $I)
    {
        $injector = new Injector();
        $injector->setAutoScanProperties(true);

        $config = array(
            'SampleService' => array(
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class,
            )
        );

        // load
        $injector->load($config);

        // inject
        $myObject = new TestObject();
        $injector->inject($myObject);

        $I->assertInstanceOf(
            SampleService::class,
            $myObject->sampleService
        );

        // also tests that ID can be the key in the array
        $config = array(
            'SampleService' => array(
                'src' => TEST_SERVICES . '/AnotherService.php',
                'class' => AnotherService::class,
            )
        );
        // , 'id' => SampleService::class));
        // load
        $injector->load($config);

        $injector->inject($myObject);
        $I->assertInstanceOf(
            AnotherService::class,
            $myObject->sampleService
        );
    }

    public function testUpdateSpec(UnitTester $I)
    {
        $injector = new Injector();
        $services = array(
            AnotherService::class => array(
                'src' => TEST_SERVICES . '/AnotherService.php',
                'properties' => array(
                    'filters' => array(
                        'One',
                        'Two',
                    )
                ),
            )
        );

        $injector->load($services);

        $injector->updateSpec(AnotherService::class, 'filters', 'Three');
        $another = $injector->get(AnotherService::class);

        $I->assertEquals(3, count($another->filters));
        $I->assertEquals('Three', $another->filters[2]);
    }

    public function testConstantUsage(UnitTester $I)
    {
        $injector = new Injector();
        $services = array(
            AnotherService::class => array(
                'properties' => array(
                    'filters' => array(
                        '`BASE_PATH`',
                        '`TEMP_PATH`',
                        '`NOT_DEFINED`',
                        'THIRDPARTY_DIR' // Not back-tick escaped
                    )
                ),
            )
        );

        $injector->load($services);
        $another = $injector->get(AnotherService::class);
        $I->assertEquals(
            [
                BASE_PATH,
                TEMP_PATH,
                null,
                'THIRDPARTY_DIR',
            ],
            $another->filters
        );
    }

    public function testAutoSetInjector(UnitTester $I)
    {
        $injector = new Injector();
        $injector->setAutoScanProperties(true);
        $injector->addAutoProperty('auto', 'somevalue');
        $config = array(
            'SampleService' => array(
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class
            )
        );
        $injector->load($config);

        $I->assertTrue($injector->has('SampleService'));
        $I->assertEquals(
            'SampleService',
            $injector->getServiceName('SampleService')
        );
        // We expect a false because the AnotherService::class is actually
        // just a replacement of the SilverStripe\Core\Tests\Injector\AopProxyServiceTest\SampleService

        $myObject = new TestObject();

        $injector->inject($myObject);

        $I->assertInstanceOf(
            SampleService::class,
            $myObject->sampleService
        );
        $I->assertEquals($myObject->auto, 'somevalue');
    }

    public function testSettingSpecificProperty(UnitTester $I)
    {
        $injector = new Injector();
        $config = array(AnotherService::class);
        $injector->load($config);
        $injector->setInjectMapping(TestObject::class, 'sampleService', AnotherService::class);
        $testObject = $injector->get(TestObject::class);

        $I->assertInstanceOf(
            AnotherService::class,
            $testObject->sampleService
        );
    }

    public function testSettingSpecificMethod(UnitTester $I)
    {
        $injector = new Injector();
        $config = array(AnotherService::class);
        $injector->load($config);
        $injector->setInjectMapping(TestObject::class, 'setSomething', AnotherService::class, 'method');

        $testObject = $injector->get(TestObject::class);

        $I->assertInstanceOf(
            AnotherService::class,
            $testObject->sampleService
        );
    }

    public function testInjectingScopedService(UnitTester $I)
    {
        $injector = new Injector();

        $config = array(
            AnotherService::class,
            'SilverStripe\Core\Tests\Injector\AopProxyServiceTest\AnotherService.DottedChild'   => SampleService::class,
        );

        $injector->load($config);

        $service = $injector->get('SilverStripe\Core\Tests\Injector\AopProxyServiceTest\AnotherService.DottedChild');
        $I->assertInstanceOf(SampleService::class, $service);

        $service = $injector->get('SilverStripe\Core\Tests\Injector\AopProxyServiceTest\AnotherService.Subset');
        $I->assertInstanceOf(AnotherService::class, $service);

        $injector->setInjectMapping(TestObject::class, 'sampleService', 'SilverStripe\Core\Tests\Injector\AopProxyServiceTest\AnotherService.Geronimo');
        $testObject = $injector->create(TestObject::class);
        $I->assertEquals(get_class($testObject->sampleService), AnotherService::class);

        $injector->setInjectMapping(TestObject::class, 'sampleService', 'SilverStripe\Core\Tests\Injector\AopProxyServiceTest\AnotherService.DottedChild.AnotherDown');
        $testObject = $injector->create(TestObject::class);
        $I->assertEquals(get_class($testObject->sampleService), SampleService::class);
    }

    public function testInjectUsingConstructor(UnitTester $I)
    {
        $injector = new Injector();
        $config = array(
            'SampleService' => array(
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class,
                'constructor' => array(
                    'val1',
                    'val2',
                )
            )
        );

        $injector->load($config);
        $sample = $injector->get('SampleService');
        $I->assertEquals($sample->constructorVarOne, 'val1');
        $I->assertEquals($sample->constructorVarTwo, 'val2');

        $injector = new Injector();
        $config = array(
            'AnotherService' => AnotherService::class,
            'SampleService' => array(
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class,
                'constructor' => array(
                    'val1',
                    '%$AnotherService',
                )
            )
        );

        $injector->load($config);
        $sample = $injector->get('SampleService');
        $I->assertEquals($sample->constructorVarOne, 'val1');
        $I->assertInstanceOf(
            AnotherService::class,
            $sample->constructorVarTwo
        );

        $injector = new Injector();
        $config = array(
            'SampleService' => array(
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class,
                'constructor' => array(
                    'val1',
                    'val2',
                )
            )
        );

        $injector->load($config);
        $sample = $injector->get('SampleService');
        $I->assertEquals($sample->constructorVarOne, 'val1');
        $I->assertEquals($sample->constructorVarTwo, 'val2');

        // test constructors on prototype
        $injector = new Injector();
        $config = array(
            'SampleService' => array(
                'type'  => 'prototype',
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class,
                'constructor' => array(
                    'val1',
                    'val2',
                )
            )
        );

        $injector->load($config);
        $sample = $injector->get('SampleService');
        $I->assertEquals($sample->constructorVarOne, 'val1');
        $I->assertEquals($sample->constructorVarTwo, 'val2');

        $again = $injector->get('SampleService');
        $I->assertFalse($sample === $again);

        $I->assertEquals($sample->constructorVarOne, 'val1');
        $I->assertEquals($sample->constructorVarTwo, 'val2');
    }

    public function testInjectUsingSetter(UnitTester $I)
    {
        $injector = new Injector();
        $injector->setAutoScanProperties(true);
        $config = array(
            'SampleService' => array(
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class,
            )
        );

        $injector->load($config);
        $I->assertTrue($injector->has('SampleService'));
        $I->assertEquals('SampleService', $injector->getServiceName('SampleService'));

        $myObject = new OtherTestObject();
        $injector->inject($myObject);

        $I->assertInstanceOf(
            SampleService::class,
            $myObject->s()
        );

        // and again because it goes down a different code path when setting things
        // based on the inject map
        $myObject = new OtherTestObject();
        $injector->inject($myObject);

        $I->assertInstanceOf(
            SampleService::class,
            $myObject->s()
        );
    }

    // make sure we can just get any arbitrary object - it should be created for us
    public function testInstantiateAnObjectViaGet(UnitTester $I)
    {
        $injector = new Injector();
        $injector->setAutoScanProperties(true);
        $config = array(
            'SampleService' => array(
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class,
            )
        );

        $injector->load($config);
        $I->assertTrue($injector->has('SampleService'));
        $I->assertEquals('SampleService', $injector->getServiceName('SampleService'));

        $myObject = $injector->get(OtherTestObject::class);
        $I->assertInstanceOf(
            SampleService::class,
            $myObject->s()
        );

        // and again because it goes down a different code path when setting things
        // based on the inject map
        $myObject = $injector->get(OtherTestObject::class);
        $I->assertInstanceOf(SampleService::class, $myObject->s());
    }

    public function testCircularReference(UnitTester $I)
    {
        $services = array(
            'CircularOne' => CircularOne::class,
            'CircularTwo' => CircularTwo::class
        );
        $injector = new Injector($services);
        $injector->setAutoScanProperties(true);

        $obj = $injector->get(NeedsBothCirculars::class);

        $I->assertTrue($obj->circularOne instanceof CircularOne);
        $I->assertTrue($obj->circularTwo instanceof CircularTwo);
    }

    public function testPrototypeObjects(UnitTester $I)
    {
        $services = array(
            'CircularOne' => CircularOne::class,
            'CircularTwo' => CircularTwo::class,
            'NeedsBothCirculars' => array(
                'class' => NeedsBothCirculars::class,
                'type' => 'prototype'
            )
        );
        $injector = new Injector($services);
        $injector->setAutoScanProperties(true);
        $obj1 = $injector->get('NeedsBothCirculars');
        $obj2 = $injector->get('NeedsBothCirculars');

        // if this was the same object, then $obj1->var would now be two
        $obj1->var = 'one';
        $obj2->var = 'two';

        $I->assertTrue($obj1->circularOne instanceof CircularOne);
        $I->assertTrue($obj1->circularTwo instanceof CircularTwo);

        $I->assertEquals($obj1->circularOne, $obj2->circularOne);
        $I->assertNotEquals($obj1, $obj2);
    }

    public function testSimpleInstantiation(UnitTester $I)
    {
        $services = array(
            'CircularOne' => CircularOne::class,
            'CircularTwo' => CircularTwo::class
        );
        $injector = new Injector($services);

        // similar to the above, but explicitly instantiating this object here
        $obj1 = $injector->create(NeedsBothCirculars::class);
        $obj2 = $injector->create(NeedsBothCirculars::class);

        // if this was the same object, then $obj1->var would now be two
        $obj1->var = 'one';
        $obj2->var = 'two';

        $I->assertEquals($obj1->circularOne, $obj2->circularOne);
        $I->assertNotEquals($obj1, $obj2);
    }

    public function testCreateWithConstructor(UnitTester $I)
    {
        $injector = new Injector();
        $obj = $injector->create(CircularTwo::class, 'param');
        $I->assertEquals($obj->otherVar, 'param');
    }

    public function testSimpleSingleton(UnitTester $I)
    {
        $injector = new Injector();

        $one = $injector->create(CircularOne::class);
        $two = $injector->create(CircularOne::class);

        $I->assertFalse($one === $two);

        $one = $injector->get(CircularTwo::class);
        $two = $injector->get(CircularTwo::class);

        $I->assertTrue($one === $two);
    }

    public function testOverridePriority(UnitTester $I)
    {
        $injector = new Injector();
        $injector->setAutoScanProperties(true);
        $config = array(
            'SampleService' => array(
                'src' => TEST_SERVICES . '/SampleService.php',
                'class' => SampleService::class,
                'priority' => 10,
            )
        );

        // load
        $injector->load($config);

        // inject
        $myObject = new TestObject();
        $injector->inject($myObject);

        $I->assertInstanceOf(SampleService::class, $myObject->sampleService);

        $config = array(
            array(
                'src' => TEST_SERVICES . '/AnotherService.php',
                'class' => AnotherService::class,
                'id' => 'SampleService',
                'priority' => 1,
            )
        );
        // load
        $injector->load($config);

        $injector->inject($myObject);
        $I->assertInstanceOf(
            SampleService::class,
            $myObject->sampleService
        );
    }

    /**
     * Specific test method to illustrate various ways of setting a requirements backend
     */
    public function testRequirementsSettingOptions(UnitTester $I)
    {
        $injector = new Injector();
        $config = array(
            OriginalRequirementsBackend::class,
            NewRequirementsBackend::class,
            DummyRequirements::class => array(
                'constructor' => array(
                    '%$' . OriginalRequirementsBackend::class
                )
            )
        );

        $injector->load($config);

        $requirements = $injector->get(DummyRequirements::class);
        $I->assertInstanceOf(
            OriginalRequirementsBackend::class,
            $requirements->backend
        );

        // just overriding the definition here
        $injector->load(
            array(
                DummyRequirements::class => array(
                    'constructor' => array(
                        '%$' . NewRequirementsBackend::class
                    )
                )
            )
        );

        // requirements should have been reinstantiated with the new bean setting
        $requirements = $injector->get(DummyRequirements::class);
        $I->assertInstanceOf(
            NewRequirementsBackend::class,
            $requirements->backend
        );
    }

    /**
     * disabled for now
     */
    public function testStaticInjections(UnitTester $I)
    {
        $injector = new Injector();
        $config = array(
            NewRequirementsBackend::class,
        );

        $injector->load($config);

        $si = $injector->get(TestStaticInjections::class);
        $I->assertInstanceOf(
            NewRequirementsBackend::class,
            $si->backend
        );
    }

    public function testSetterInjections(UnitTester $I)
    {
        $injector = new Injector();
        $config = array(
            NewRequirementsBackend::class,
        );

        $injector->load($config);

        $si = $injector->get(TestSetterInjections::class);
        $I->assertInstanceOf(
            NewRequirementsBackend::class,
            $si->getBackend()
        );
    }

    public function testCustomObjectCreator(UnitTester $I)
    {
        $injector = new Injector();
        $injector->setObjectCreator(new SSObjectCreator($injector));
        $config = array(
            OriginalRequirementsBackend::class,
            DummyRequirements::class => array(
                'class' => DummyRequirements::class . '(\'%$' . OriginalRequirementsBackend::class . '\')'
            )
        );
        $injector->load($config);

        $requirements = $injector->get(DummyRequirements::class);
        $I->assertEquals(OriginalRequirementsBackend::class, get_class($requirements->backend));
    }

    public function testInheritedConfig(UnitTester $I)
    {

        // Test that child class does not automatically inherit config
        $injector = new Injector(array('locator' => SilverStripeServiceConfigurationLocator::class));
        Config::modify()->merge(
            Injector::class,
            MyParentClass::class,
            [
                'properties' => ['one' => 'the one'],
                'class' => MyParentClass::class,
            ]
        );
        $obj = $injector->get(MyParentClass::class);
        $I->assertInstanceOf(MyParentClass::class, $obj);
        $I->assertEquals($obj->one, 'the one');

        // Class isn't inherited and parent properties are ignored
        $obj = $injector->get(MyChildClass::class);
        $I->assertInstanceOf(MyChildClass::class, $obj);
        $I->assertNotEquals($obj->one, 'the one');

        // Set child class as alias
        $injector = new Injector(
            array(
                'locator' => SilverStripeServiceConfigurationLocator::class
            )
        );
        Config::modify()->merge(
            Injector::class,
            MyChildClass::class,
            '%$' . MyParentClass::class
        );

        // Class isn't inherited and parent properties are ignored
        $obj = $injector->get(MyChildClass::class);
        $I->assertInstanceOf(MyParentClass::class, $obj);
        $I->assertEquals($obj->one, 'the one');
    }

    public function testSameNamedSingeltonPrototype(UnitTester $I)
    {
        $injector = new Injector();

        // get a singleton object
        $object = $injector->get(NeedsBothCirculars::class);
        $object->var = 'One';

        $again = $injector->get(NeedsBothCirculars::class);
        $I->assertEquals($again->var, 'One');

        // create a NEW instance object
        $new = $injector->create(NeedsBothCirculars::class);
        $I->assertNull($new->var);

        // this will trigger a problem below
        $new->var = 'Two';

        $again = $injector->get(NeedsBothCirculars::class);
        $I->assertEquals($again->var, 'One');
    }

    public function testConvertServicePropertyOnCreate(UnitTester $I)
    {
        // make sure convert service property is not called on direct calls to create, only on configured
        // declarations to avoid un-needed function calls
        $injector = new Injector();
        $item = $injector->create(ConstructableObject::class, '%$' . TestObject::class);
        $I->assertEquals('%$' . TestObject::class, $item->property);

        // do it again but have test object configured as a constructor dependency
        $injector = new Injector();
        $config = array(
            ConstructableObject::class => array(
                'constructor' => array(
                    '%$' . TestObject::class
                )
            )
        );

        $injector->load($config);
        $item = $injector->get(ConstructableObject::class);
        $I->assertTrue($item->property instanceof TestObject);

        // and with a configured object defining TestObject to be something else!
        $injector = new Injector(array('locator' => InjectorTestConfigLocator::class));
        $config = array(
            ConstructableObject::class => array(
                'constructor' => array(
                    '%$' . TestObject::class
                )
            ),
        );

        $injector->load($config);
        $item = $injector->get(ConstructableObject::class);
        $I->assertTrue($item->property instanceof ConstructableObject);

        $I->assertInstanceOf(OtherTestObject::class, $item->property->property);
    }

    public function testNamedServices(UnitTester $I)
    {
        $injector = new Injector();
        $service  = new TestObject();
        $service->setSomething('injected');

        // Test registering with non-class name
        $injector->registerService($service, 'NamedService');
        $I->assertTrue($injector->has('NamedService'));
        $I->assertEquals($service, $injector->get('NamedService'));

        // Unregister service by name
        $injector->unregisterNamedObject('NamedService');
        $I->assertFalse($injector->has('NamedService'));

        // Test registered with class name
        $injector->registerService($service);
        $I->assertTrue($injector->has(TestObject::class));
        $I->assertEquals($service, $injector->get(TestObject::class));

        // Unregister service by class
        $injector->unregisterNamedObject(TestObject::class);
        $I->assertFalse($injector->has(TestObject::class));
    }

    public function testCreateConfiggedObjectWithCustomConstructorArgs(UnitTester $I)
    {
        // need to make sure that even if the config defines some constructor params,
        // that we take our passed in constructor args instead
        $injector = new Injector(array('locator' => InjectorTestConfigLocator::class));

        $item = $injector->create('ConfigConstructor', 'othervalue');
        $I->assertEquals($item->property, 'othervalue');
    }

    /**
     * Tests creating a service with a custom factory.
     */
    public function testCustomFactory(UnitTester $I)
    {
        $injector = new Injector(
            array(
                'service' => array('factory' => 'factory', 'constructor' => array(1, 2, 3))
            )
        );

        $factory = Stub::makeEmpty(Factory::class, array(
            'create' => Expected::once(function () {
                return new TestObject();
            }),
        ));

        $injector->registerService($factory, 'factory');

        $I->assertInstanceOf(TestObject::class, $injector->get('service'));
    }

    public function testMethods(UnitTester $I)
    {
        // do it again but have test object configured as a constructor dependency
        $injector = new Injector();
        $config = array(
            'A' => array(
                'class' => TestObject::class,
            ),
            'B' => array(
                'class' => TestObject::class,
            ),
            'TestService' => array(
                'class' => TestObject::class,
                'calls' => array(
                    array('myMethod', array('%$A')),
                    array('myMethod', array('%$B')),
                    array('noArgMethod')
                )
            )
        );

        $injector->load($config);
        $item = $injector->get('TestService');
        $I->assertTrue($item instanceof TestObject);
        $I->assertEquals(
            array($injector->get('A'), $injector->get('B'), 'noArgMethod called'),
            $item->methodCalls
        );
    }

    public function testNonExistentMethods(UnitTester $I)
    {
        $I->expectException(\InvalidArgumentException::class, function () {
            $injector = new Injector();
            $config = array(
                'TestService' => array(
                    'class' => TestObject::class,
                    'calls' => array(
                        array('thisDoesntExist')
                    )
                )
            );

            $injector->load($config);
            $injector->get('TestService');
        });
    }

    public function testProtectedMethods(UnitTester $I)
    {
        $I->expectException(\InvalidArgumentException::class, function () {
            $injector = new Injector();
            $config = array(
                'TestService' => array(
                    'class' => TestObject::class,
                    'calls' => array(
                        array('protectedMethod')
                    )
                )
            );

            $injector->load($config);
            $injector->get('TestService');
        });
    }

    public function testTooManyArrayValues(UnitTester $I)
    {
        $I->expectException(\InvalidArgumentException::class, function () {
            $injector = new Injector();
            $config = array(
                'TestService' => array(
                    'class' => TestObject::class,
                    'calls' => array(
                        array('method', array('args'), 'what is this?')
                    )
                )
            );

            $injector->load($config);
            $injector->get('TestService');
        });
    }

    public function testGetThrowsOnNotFound(UnitTester $I)
    {
        $I->expectException(InjectorNotFoundException::class, function () {
            $injector = new Injector();
            $injector->get('UnknownService');
        });
    }

    public function testGetTrimsWhitespaceFromNames(UnitTester $I)
    {
        $injector = new Injector;

        $I->assertInstanceOf(MyChildClass::class, $injector->get('    ' . MyChildClass::class . '     '));
    }

    /**
     * Test nesting of injector
     */
    public function testNest(UnitTester $I)
    {
        // Test services
        $config = array(
            NewRequirementsBackend::class,
        );
        Injector::inst()->load($config);
        $si = Injector::inst()->get(TestStaticInjections::class);
        $I->assertInstanceOf(TestStaticInjections::class, $si);
        $I->assertInstanceOf(NewRequirementsBackend::class, $si->backend);
        $I->assertInstanceOf(MyParentClass::class, Injector::inst()->get(MyParentClass::class));
        $I->assertInstanceOf(MyChildClass::class, Injector::inst()->get(MyChildClass::class));

        // Test that nested injector values can be overridden
        Injector::nest();
        Injector::inst()->unregisterObjects([
            TestStaticInjections::class,
            MyParentClass::class,
        ]);
        $newsi = Injector::inst()->get(TestStaticInjections::class);
        $newsi->backend = new OriginalRequirementsBackend();
        Injector::inst()->registerService($newsi, TestStaticInjections::class);
        Injector::inst()->registerService(new MyChildClass(), MyParentClass::class);

        // Check that these overridden values are retrievable
        $si = Injector::inst()->get(TestStaticInjections::class);
        $I->assertInstanceOf(TestStaticInjections::class, $si);
        $I->assertInstanceOf(OriginalRequirementsBackend::class, $si->backend);
        $I->assertInstanceOf(MyParentClass::class, Injector::inst()->get(MyParentClass::class));
        $I->assertInstanceOf(MyParentClass::class, Injector::inst()->get(MyChildClass::class));

        // Test that unnesting restores expected behaviour
        Injector::unnest();
        $si = Injector::inst()->get(TestStaticInjections::class);
        $I->assertInstanceOf(TestStaticInjections::class, $si);
        $I->assertInstanceOf(NewRequirementsBackend::class, $si->backend);
        $I->assertInstanceOf(MyParentClass::class, Injector::inst()->get(MyParentClass::class));
        $I->assertInstanceOf(MyChildClass::class, Injector::inst()->get(MyChildClass::class));

        // Test reset of cache
        Injector::inst()->unregisterObjects([
            TestStaticInjections::class,
            MyParentClass::class,
        ]);
        $si = Injector::inst()->get(TestStaticInjections::class);
        $I->assertInstanceOf(TestStaticInjections::class, $si);
        $I->assertInstanceOf(NewRequirementsBackend::class, $si->backend);
        $I->assertInstanceOf(MyParentClass::class, Injector::inst()->get(MyParentClass::class));
        $I->assertInstanceOf(MyChildClass::class, Injector::inst()->get(MyChildClass::class));

    }
}
