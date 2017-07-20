<?php
namespace Core\Manifest;
use SilverStripe\Core\Manifest\ClassContentRemover;
use \UnitTester;

class ClassContentRemoverCest
{
    protected $fixturesPath;

    public function _before(UnitTester $I)
    {
        $this->fixturesPath = FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures';
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testRemoveClassContent(UnitTester $I)
    {
        $filePath = $this->fixturesPath . '/classcontentremover/ContentRemoverTestA.php';
        $cleanContents = ClassContentRemover::remove_class_content($filePath);

        $expected = '<?php
 namespace TestNamespace\\Testing; use TestNamespace\\{Test1, Test2, Test3}; class MyTest extends Test1 implements Test2 {}';

        $I->assertEquals($expected, $cleanContents);
    }

    public function testRemoveClassContentConditional(UnitTester $I)
    {
        $filePath = $this->fixturesPath . '/classcontentremover/ContentRemoverTestB.php';
        $cleanContents = ClassContentRemover::remove_class_content($filePath);

        $expected = '<?php
 namespace TestNamespace\\Testing; use TestNamespace\\{Test1, Test2, Test3}; if (class_exists(\'Class\')) { class MyTest extends Test1 implements Test2 {} class MyTest2 {} }';

        $I->assertEquals($expected, $cleanContents);
    }

    public function testRemoveClassContentNoClass(UnitTester $I)
    {
        $filePath = $this->fixturesPath . '/classcontentremover/ContentRemoverTestC.php';

        $cleanContents = ClassContentRemover::remove_class_content($filePath);

        $I->assertEmpty($cleanContents);
    }

    public function testRemoveClassContentSillyMethod(UnitTester $I)
    {
        $filePath = $this->fixturesPath . '/classcontentremover/ContentRemoverTestD.php';

        $cleanContents = ClassContentRemover::remove_class_content($filePath);

        $expected = '<?php
 class SomeClass {} class AnotherClass {}';

        $I->assertEquals($expected, $cleanContents);
    }
}
