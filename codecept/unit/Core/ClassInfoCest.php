<?php
namespace Core;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Tests\ClassInfoTest\BaseClass;
use SilverStripe\Core\Tests\ClassInfoTest\BaseDataClass;
use SilverStripe\Core\Tests\ClassInfoTest\ChildClass;
use SilverStripe\Core\Tests\ClassInfoTest\GrandChildClass;
use SilverStripe\Core\Tests\ClassInfoTest\HasFields;
use SilverStripe\Core\Tests\ClassInfoTest\NoFields;
use SilverStripe\Core\Tests\ClassInfoTest\WithCustomTable;
use SilverStripe\Core\Tests\ClassInfoTest\WithRelation;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;
use \UnitTester;

class ClassInfoCest
{

    public function _before()
    {
        ClassInfo::reset_db_cache();
    }

    public function testExists(UnitTester $I)
    {
        $I->assertTrue(ClassInfo::exists(ClassInfo::class));
        $I->assertTrue(ClassInfo::exists('SilverStripe\\Core\\classinfo'));
        $I->assertTrue(ClassInfo::exists('SilverStripe\\Core\\Tests\\ClassInfoTest\\BaseClass'));
        $I->assertTrue(ClassInfo::exists('SilverStripe\\Core\\Tests\\CLASSINFOTEST\\BaseClass'));
        $I->assertTrue(ClassInfo::exists('stdClass'));
        $I->assertTrue(ClassInfo::exists('stdCLASS'));
        $I->assertFalse(ClassInfo::exists('SomeNonExistantClass'));
    }

    public function testSubclassesFor(UnitTester $I)
    {
        $subclasses = [
            'silverstripe\\core\\tests\\classinfotest\\baseclass' => BaseClass::class,
            'silverstripe\\core\\tests\\classinfotest\\childclass' => ChildClass::class,
            'silverstripe\\core\\tests\\classinfotest\\grandchildclass' => GrandChildClass::class,
        ];
        $I->assertEquals(
            $subclasses,
            ClassInfo::subclassesFor(BaseClass::class),
            'ClassInfo::subclassesFor() returns only direct subclasses and doesnt include base class'
        );
        ClassInfo::reset_db_cache();
        $I->assertEquals(
            $subclasses,
            ClassInfo::subclassesFor('silverstripe\\core\\tests\\classinfotest\\baseclass'),
            'ClassInfo::subclassesFor() is acting in a case sensitive way when it should not'
        );
    }

    public function testClassName(UnitTester $I)
    {
        $I->assertEquals(
            UnitTester::class,
            ClassInfo::class_name($I)
        );
        $I->assertEquals(
            UnitTester::class,
            ClassInfo::class_name('UnitTester')
        );
        $I->assertEquals(
            UnitTester::class,
            ClassInfo::class_name('UNIttesTer')
        );
    }

    public function testNonClassName(UnitTester $I)
    {
        $I->expectException(\ReflectionException::class, function () {
            ClassInfo::class_name('IAmAClassThatDoesNotExist');
        });
    }

    public function testClassesForFolder(UnitTester $I)
    {
        $classes = ClassInfo::classes_for_folder(ltrim(FRAMEWORK_DIR . '/tests', '/'));
        $I->assertArrayHasKey(
            'silverstripe\\core\\tests\\classinfotest\\baseclass',
            $classes,
            'ClassInfo::classes_for_folder() returns additional classes not matching the filename'
        );
        $I->assertContains(
            BaseClass::class,
            $classes,
            'ClassInfo::classes_for_folder() returns additional classes not matching the filename'
        );
    }

    /**
     * @covers \SilverStripe\Core\ClassInfo::ancestry()
     */
    public function testAncestry(UnitTester $I)
    {
        $ancestry = ClassInfo::ancestry(ChildClass::class);
        $expect = [
            'silverstripe\\view\\viewabledata' => ViewableData::class,
            'silverstripe\\orm\\dataobject' => DataObject::class,
            'silverstripe\\core\tests\classinfotest\\baseclass' => BaseClass::class,
            'silverstripe\\core\tests\classinfotest\\childclass' => ChildClass::class,
        ];
        $I->assertEquals($expect, $ancestry);

        ClassInfo::reset_db_cache();
        $I->assertEquals(
            $expect,
            ClassInfo::ancestry('silverstripe\\core\\tests\\classINFOtest\\Childclass')
        );

        ClassInfo::reset_db_cache();
        $ancestry = ClassInfo::ancestry(ChildClass::class, true);
        $I->assertEquals(
            [ 'silverstripe\\core\tests\classinfotest\\baseclass' => BaseClass::class ],
            $ancestry,
            '$tablesOnly option excludes memory-only inheritance classes'
        );
    }

    /**
     * @covers \SilverStripe\Core\ClassInfo::dataClassesFor()
     */
    public function testDataClassesFor(UnitTester $I)
    {
        $expect = [
            'silverstripe\\core\\tests\\classinfotest\\basedataclass' => BaseDataClass::class,
            'silverstripe\\core\\tests\\classinfotest\\hasfields' => HasFields::class,
            'silverstripe\\core\\tests\\classinfotest\\withrelation' => WithRelation::class,
            'silverstripe\\core\\tests\\classinfotest\\withcustomtable' => WithCustomTable::class,
        ];
        $classes = array(
            BaseDataClass::class,
            NoFields::class,
            HasFields::class,
        );

        ClassInfo::reset_db_cache();
        $I->assertEquals($expect, ClassInfo::dataClassesFor($classes[0]));
        ClassInfo::reset_db_cache();
        $I->assertEquals($expect, ClassInfo::dataClassesFor(strtoupper($classes[0])));
        ClassInfo::reset_db_cache();
        $I->assertEquals($expect, ClassInfo::dataClassesFor($classes[1]));

        $expect = [
            'silverstripe\\core\\tests\\classinfotest\\basedataclass' => BaseDataClass::class,
            'silverstripe\\core\\tests\\classinfotest\\hasfields' => HasFields::class,
        ];

        ClassInfo::reset_db_cache();
        $I->assertEquals($expect, ClassInfo::dataClassesFor($classes[2]));
        ClassInfo::reset_db_cache();
        $I->assertEquals($expect, ClassInfo::dataClassesFor(strtolower($classes[2])));
    }
}
