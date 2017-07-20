<?php
namespace Core;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Tests\ObjectTest\BaseObject;
use SilverStripe\Core\Tests\ObjectTest\CreateTest;
use SilverStripe\Core\Tests\ObjectTest\Extending;
use SilverStripe\Core\Tests\ObjectTest\ExtendTest;
use SilverStripe\Core\Tests\ObjectTest\ExtendTest1;
use SilverStripe\Core\Tests\ObjectTest\ExtendTest2;
use SilverStripe\Core\Tests\ObjectTest\ExtendTest3;
use SilverStripe\Core\Tests\ObjectTest\ExtendTest4;
use SilverStripe\Core\Tests\ObjectTest\ExtensionRemoveTest;
use SilverStripe\Core\Tests\ObjectTest\ExtensionTest;
use SilverStripe\Core\Tests\ObjectTest\ExtensionTest2;
use SilverStripe\Core\Tests\ObjectTest\ExtensionTest3;
use SilverStripe\Core\Tests\ObjectTest\MyObject;
use SilverStripe\Core\Tests\ObjectTest\MySubObject;
use SilverStripe\Core\Tests\ObjectTest\T2;
use SilverStripe\Core\Tests\ObjectTest\TestExtension;
use SilverStripe\Versioned\Versioned;
use \UnitTester;

class ObjectCest
{
    public function _before(UnitTester $I)
    {
        Injector::inst()->unregisterObjects([
            Extension::class,
            BaseObject::class,
        ]);
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testHasmethodBehaviour(UnitTester $I)
    {
        $obj = new ExtendTest();

        $I->assertTrue($obj->hasMethod('extendableMethod'), "Extension method found in original spelling");
        $I->assertTrue($obj->hasMethod('ExTendableMethod'), "Extension method found case-insensitive");

        $objs = array();
        $objs[] = new T2();
        $objs[] = new T2();
        $objs[] = new T2();

        // All these methods should exist and return true
        $trueMethods = array('testMethod','otherMethod','someMethod','t1cMethod','normalMethod', 'failoverCallback');

        foreach ($objs as $i => $obj) {
            foreach ($trueMethods as $method) {
                $methodU = strtoupper($method);
                $methodL = strtoupper($method);
                $I->assertTrue($obj->hasMethod($method), "Test that obj#$i has method $method");
                $I->assertTrue($obj->hasMethod($methodU), "Test that obj#$i has method $methodU");
                $I->assertTrue($obj->hasMethod($methodL), "Test that obj#$i has method $methodL");

                $I->assertTrue($obj->$method(), "Test that obj#$i can call method $method");
                $I->assertTrue($obj->$methodU(), "Test that obj#$i can call method $methodU");
                $I->assertTrue($obj->$methodL(), "Test that obj#$i can call method $methodL");
            }

            $I->assertTrue($obj->hasMethod('Wrapping'), "Test that obj#$i has method Wrapping");
            $I->assertTrue($obj->hasMethod('WRAPPING'), "Test that obj#$i has method WRAPPING");
            $I->assertTrue($obj->hasMethod('wrapping'), "Test that obj#$i has method wrapping");

            $I->assertEquals("Wrapping", $obj->Wrapping(), "Test that obj#$i can call method Wrapping");
            $I->assertEquals("Wrapping", $obj->WRAPPING(), "Test that obj#$i can call method WRAPPIGN");
            $I->assertEquals("Wrapping", $obj->wrapping(), "Test that obj#$i can call method wrapping");
        }
    }

    public function testSingletonCreation(UnitTester $I)
    {
        $myObject = MyObject::singleton();
        $I->assertInstanceOf(
            MyObject::class,
            $myObject,
            'singletons are creating a correct class instance'
        );
        $mySubObject = MySubObject::singleton();
        $I->assertInstanceOf(
            MySubObject::class,
            $mySubObject,
            'singletons are creating a correct subclass instance'
        );

        $myFirstObject = MyObject::singleton();
        $mySecondObject = MyObject::singleton();
        $I->assertTrue(
            $myFirstObject === $mySecondObject,
            'singletons are using the same object on subsequent calls'
        );
    }

    public function testStaticGetterMethod(UnitTester $I)
    {
        $obj = singleton(MyObject::class);
        $I->assertEquals(
            'MyObject',
            $obj->stat('mystaticProperty'),
            'Uninherited statics through stat() on a singleton behave the same as built-in PHP statics'
        );
    }

    public function testStaticInheritanceGetters(UnitTester $I)
    {
        $subObj = singleton(MyObject::class);
        $I->assertEquals(
            $subObj->stat('mystaticProperty'),
            'MyObject',
            'Statics defined on a parent class are available through stat() on a subclass'
        );
    }

    public function testStaticSettingOnSingletons(UnitTester $I)
    {
        $singleton1 = singleton(MyObject::class);
        $singleton2 = singleton(MyObject::class);
        $singleton1->set_stat('mystaticProperty', 'changed');
        $I->assertEquals(
            $singleton2->stat('mystaticProperty'),
            'changed',
            'Statics setting is populated throughout singletons without explicitly clearing cache'
        );
    }

    public function testStaticSettingOnInstances(UnitTester $I)
    {
        $instance1 = new MyObject();
        $instance2 = new MyObject();
        $instance1->set_stat('mystaticProperty', 'changed');
        $I->assertEquals(
            $instance2->stat('mystaticProperty'),
            'changed',
            'Statics setting through set_stat() is populated throughout instances without explicitly clearing cache'
        );
    }

    /**
     * Tests that {@link Object::create()} correctly passes all arguments to the new object
     */
    public function testCreateWithArgs(UnitTester $I)
    {
        $createdObj = CreateTest::create('arg1', 'arg2', array(), null, 'arg5');
        $I->assertEquals($createdObj->constructArguments, array('arg1', 'arg2', array(), null, 'arg5'));
    }

    public function testCreateLateStaticBinding(UnitTester $I)
    {
        $createdObj = CreateTest::create('arg1', 'arg2', array(), null, 'arg5');
        $I->assertEquals($createdObj->constructArguments, array('arg1', 'arg2', array(), null, 'arg5'));
    }

    /**
     * Tests {@link Object::singleton()}
     */
    public function testSingleton(UnitTester $I)
    {
        $inst = Controller::singleton();
        $I->assertInstanceOf(Controller::class, $inst);
        $inst2 = Controller::singleton();
        $I->assertSame($inst2, $inst);
    }

    public function testGetExtensions(UnitTester $I)
    {
        $I->assertEquals(
            array(
                'SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1',
                "SilverStripe\\Core\\Tests\\ObjectTest\\ExtendTest2",
            ),
            ExtensionTest::get_extensions()
        );
        $I->assertEquals(
            array(
                'SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1',
                "SilverStripe\\Core\\Tests\\ObjectTest\\ExtendTest2('FOO', 'BAR')",
            ),
            ExtensionTest::get_extensions(null, true)
        );
        $inst = new ExtensionTest();
        $extensions = $inst->getExtensionInstances();
        $I->assertEquals(count($extensions), 2);
        $I->assertInstanceOf(
            ExtendTest1::class,
            $extensions[ExtendTest1::class]
        );
        $I->assertInstanceOf(
            ExtendTest2::class,
            $extensions[ExtendTest2::class]
        );
        $I->assertInstanceOf(
            ExtendTest1::class,
            $inst->getExtensionInstance(ExtendTest1::class)
        );
        $I->assertInstanceOf(
            ExtendTest2::class,
            $inst->getExtensionInstance(ExtendTest2::class)
        );
    }

    /**
     * Tests {@link Object::has_extension()}, {@link Object::add_extension()}
     */
    public function testHasAndAddExtension(UnitTester $I)
    {
        // ObjectTest_ExtendTest1 is built in via $extensions
        $I->assertTrue(
            ExtensionTest::has_extension('SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1'),
            "Extensions are detected when set on Object::\$extensions on has_extension() without case-sensitivity"
        );
        $I->assertTrue(
            ExtensionTest::has_extension(ExtendTest1::class),
            "Extensions are detected when set on Object::\$extensions on has_extension() without case-sensitivity"
        );
        $I->assertTrue(
            singleton(ExtensionTest::class)->hasExtension(ExtendTest1::class),
            "Extensions are detected when set on Object::\$extensions on instance hasExtension() without"
            . " case-sensitivity"
        );

        // ObjectTest_ExtendTest2 is built in via $extensions (with parameters)
        $I->assertTrue(
            ExtensionTest::has_extension(ExtendTest2::class),
            "Extensions are detected with static has_extension() when set on Object::\$extensions with"
            . " additional parameters"
        );
        $I->assertTrue(
            singleton(ExtensionTest::class)->hasExtension(ExtendTest2::class),
            "Extensions are detected with instance hasExtension() when set on Object::\$extensions with"
            . " additional parameters"
        );
        $I->assertFalse(
            ExtensionTest::has_extension(ExtendTest3::class),
            "Other extensions available in the system are not present unless explicitly added to this object"
            . " when checking through has_extension()"
        );
        $I->assertFalse(
            singleton(ExtensionTest::class)->hasExtension(ExtendTest3::class),
            "Other extensions available in the system are not present unless explicitly added to this object"
            . " when checking through instance hasExtension()"
        );

        // ObjectTest_ExtendTest3 is added manually
        ExtensionTest::add_extension(ExtendTest3::class .'("Param")');
        $I->assertTrue(
            ExtensionTest::has_extension(ExtendTest3::class),
            "Extensions are detected with static has_extension() when added through add_extension()"
        );
        // ExtendTest4 is added manually
        ExtensionTest3::add_extension(ExtendTest4::class . '("Param")');
        // test against ObjectTest_ExtendTest3, not ObjectTest_ExtendTest3
        $I->assertTrue(
            ExtensionTest3::has_extension(ExtendTest4::class),
            "Extensions are detected with static has_extension() when added through add_extension()"
        );
        // test against ObjectTest_ExtendTest3, not ExtendTest4 to test if it picks up
        // the sub classes of ObjectTest_ExtendTest3
        $I->assertTrue(
            ExtensionTest3::has_extension(ExtendTest3::class),
            "Sub-Extensions are detected with static has_extension() when added through add_extension()"
        );
        // strictly test against ObjectTest_ExtendTest3, not ExtendTest4 to test if it picks up
        // the sub classes of ObjectTest_ExtendTest3
        $I->assertFalse(
            ExtensionTest3::has_extension(ExtendTest3::class, null, true),
            "Sub-Extensions are detected with static has_extension() when added through add_extension()"
        );
        // a singleton() wouldn't work as its already initialized
        $objectTest_ExtensionTest = new ExtensionTest();
        $I->assertTrue(
            $objectTest_ExtensionTest->hasExtension(ExtendTest3::class),
            "Extensions are detected with instance hasExtension() when added through add_extension()"
        );

        // @todo At the moment, this does NOT remove the extension due to parameterized naming,
        //  meaning the extension will remain added in further test cases
        ExtensionTest::remove_extension(ExtendTest3::class);
    }

    public function testRemoveExtension(UnitTester $I)
    {
        // manually add ObjectTest_ExtendTest2
        ExtensionRemoveTest::add_extension(ExtendTest2::class);
        $I->assertTrue(
            ExtensionRemoveTest::has_extension(ExtendTest2::class),
            "Extension added through \$add_extension() are added correctly"
        );

        ExtensionRemoveTest::remove_extension(ExtendTest2::class);
        $I->assertFalse(
            ExtensionRemoveTest::has_extension(ExtendTest2::class),
            "Extension added through \$add_extension() are detected as removed in has_extension()"
        );
        $I->assertFalse(
            singleton(ExtensionRemoveTest::class)->hasExtension(ExtendTest2::class),
            "Extensions added through \$add_extension() are detected as removed in instances through hasExtension()"
        );

        // ObjectTest_ExtendTest1 is already present in $extensions
        ExtensionRemoveTest::remove_extension(ExtendTest1::class);

        $I->assertFalse(
            ExtensionRemoveTest::has_extension(ExtendTest1::class),
            "Extension added through \$extensions are detected as removed in has_extension()"
        );

        $objectTest_ExtensionRemoveTest = new ExtensionRemoveTest();
        $I->assertFalse(
            $objectTest_ExtensionRemoveTest->hasExtension(ExtendTest1::class),
            "Extensions added through \$extensions are detected as removed in instances through hasExtension()"
        );
    }

    public function testRemoveExtensionWithParameters(UnitTester $I)
    {
        ExtensionRemoveTest::add_extension(ExtendTest2::class.'("MyParam")');

        $I->assertTrue(
            ExtensionRemoveTest::has_extension(ExtendTest2::class),
            "Extension added through \$add_extension() are added correctly"
        );

        ExtensionRemoveTest::remove_extension(ExtendTest2::class);
        $I->assertFalse(
            ExtensionRemoveTest::has_extension(ExtendTest2::class),
            "Extension added through \$add_extension() are detected as removed in has_extension()"
        );

        $objectTest_ExtensionRemoveTest = new ExtensionRemoveTest();
        $I->assertFalse(
            $objectTest_ExtensionRemoveTest->hasExtension(ExtendTest2::class),
            "Extensions added through \$extensions are detected as removed in instances through hasExtension()"
        );
    }

    public function testIsA(UnitTester $I)
    {
        $I->assertTrue(MyObject::create() instanceof BaseObject);
        $I->assertTrue(MyObject::create() instanceof MyObject);
    }

    /**
     * Tests {@link Object::hasExtension() and Object::getExtensionInstance()}
     */
    public function testExtInstance(UnitTester $I)
    {
        $obj = new ExtensionTest2();

        $I->assertTrue($obj->hasExtension(TestExtension::class));
        $I->assertTrue($obj->getExtensionInstance(TestExtension::class) instanceof TestExtension);
    }

    public function testExtend(UnitTester $I)
    {
        $object   = new ExtendTest();
        $argument = 'test';

        $I->assertEquals($object->extend('extendableMethod'), array('ExtendTest2()'));
        $I->assertEquals($object->extend('extendableMethod', $argument), array('ExtendTest2(modified)'));
        $I->assertEquals($argument, 'modified');

        $I->assertEquals(
            array('ExtendTest()', 'ExtendTest2()'),
            $object->invokeWithExtensions('extendableMethod')
        );
        $arg1 = 'test';
        $arg2 = 'bob';
        $I->assertEquals(
            array('ExtendTest(test,bob)', 'ExtendTest2(modified,objectmodified)'),
            $object->invokeWithExtensions('extendableMethod', $arg1, $arg2)
        );
        $I->assertEquals('modified', $arg1);
        $I->assertEquals('objectmodified', $arg2);

        $object2 = new Extending();
        $first = 1;
        $second = 2;
        $third = 3;
        $result = $object2->getResults($first, $second, $third);
        $I->assertEquals(
            array(array('before', 'extension', 'after')),
            $result
        );
        $I->assertEquals(31, $first);
        $I->assertEquals(32, $second);
        $I->assertEquals(33, $third);
    }

    public function testParseClassSpec(UnitTester $I)
    {
        // Simple case
        $I->assertEquals(
            array(Versioned::class,array('Stage', 'Live')),
            ClassInfo::parse_class_spec("SilverStripe\\Versioned\\Versioned('Stage','Live')")
        );
        // String with commas
        $I->assertEquals(
            array(Versioned::class,array('Stage,Live', 'Stage')),
            ClassInfo::parse_class_spec("SilverStripe\\Versioned\\Versioned('Stage,Live','Stage')")
        );
        // String with quotes
        $I->assertEquals(
            array(Versioned::class,array('Stage\'Stage,Live\'Live', 'Live')),
            ClassInfo::parse_class_spec("SilverStripe\\Versioned\\Versioned('Stage\\'Stage,Live\\'Live','Live')")
        );

        // True, false and null values
        $I->assertEquals(
            array('ClassName', array('string', true, array('string', false))),
            ClassInfo::parse_class_spec('ClassName("string", true, array("string", false))')
        );
        $I->assertEquals(
            array('ClassName', array(true, false, null)),
            ClassInfo::parse_class_spec('ClassName(true, false, null)')
        );

        // Array
        $I->assertEquals(
            array('Enum',array(array('Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted')),
            ClassInfo::parse_class_spec("Enum(array('Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted')")
        );
        // Nested array
        $I->assertEquals(
            array('Enum',array(array('Accepted', 'Pending', 'Declined', array('UnsubmittedA','UnsubmittedB')),
                'Unsubmitted')),
            ClassInfo::parse_class_spec(
                "Enum(array('Accepted', 'Pending', 'Declined', array('UnsubmittedA','UnsubmittedB')), 'Unsubmitted')"
            )
        );
        // 5.4 Shorthand Array
        $I->assertEquals(
            array('Enum',array(array('Accepted', 'Pending', 'Declined', 'Unsubmitted'), 'Unsubmitted')),
            ClassInfo::parse_class_spec("Enum(['Accepted', 'Pending', 'Declined', 'Unsubmitted'], 'Unsubmitted')")
        );
        // 5.4 Nested shorthand array
        $I->assertEquals(
            array('Enum',array(array('Accepted', 'Pending', 'Declined', array('UnsubmittedA','UnsubmittedB')),
                'Unsubmitted')),
            ClassInfo::parse_class_spec(
                "Enum(['Accepted', 'Pending', 'Declined', ['UnsubmittedA','UnsubmittedB']], 'Unsubmitted')"
            )
        );

        // Associative array
        $I->assertEquals(
            array('Varchar', array(255, array('nullifyEmpty' => false))),
            ClassInfo::parse_class_spec("Varchar(255, array('nullifyEmpty' => false))")
        );
        // Nested associative array
        $I->assertEquals(
            array('Test', array('string', array('nested' => array('foo' => 'bar')))),
            ClassInfo::parse_class_spec("Test('string', array('nested' => array('foo' => 'bar')))")
        );
        // 5.4 shorthand associative array
        $I->assertEquals(
            array('Varchar', array(255, array('nullifyEmpty' => false))),
            ClassInfo::parse_class_spec("Varchar(255, ['nullifyEmpty' => false])")
        );
        // 5.4 shorthand nested associative array
        $I->assertEquals(
            array('Test', array('string', array('nested' => array('foo' => 'bar')))),
            ClassInfo::parse_class_spec("Test('string', ['nested' => ['foo' => 'bar']])")
        );

        // Namespaced class
        $I->assertEquals(
            array('Test\MyClass', array()),
            ClassInfo::parse_class_spec('Test\MyClass')
        );
        // Fully qualified namespaced class
        $I->assertEquals(
            array('\Test\MyClass', array()),
            ClassInfo::parse_class_spec('\Test\MyClass')
        );
    }
}
