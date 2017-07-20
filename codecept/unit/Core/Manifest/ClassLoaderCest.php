<?php
namespace Core\Manifest;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ClassManifest;
use \UnitTester;

class ClassLoaderCest
{
    /**
     * @var string
     */
    protected $baseManifest1;

    /**
     * @var string
     */
    protected $baseManifest2;

    /**
     * @var ClassManifest
     */
    protected $testManifest1;

    /**
     * @var ClassManifest
     */
    protected $testManifest2;

    public function _before(UnitTester $I)
    {

        $this->baseManifest1 = FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/classmanifest';
        $this->baseManifest2 = FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/classmanifest_other';
        $this->testManifest1 = new ClassManifest($this->baseManifest1);
        $this->testManifest2 = new ClassManifest($this->baseManifest2);
        $this->testManifest1->init();
        $this->testManifest2->init();
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testExclusive(UnitTester $I)
    {
        $loader = new ClassLoader();

        $loader->pushManifest($this->testManifest1);
        $I->assertTrue((bool)$loader->getItemPath('ClassA'));
        $I->assertFalse((bool)$loader->getItemPath('OtherClassA'));

        $loader->pushManifest($this->testManifest2);
        $I->assertFalse((bool)$loader->getItemPath('ClassA'));
        $I->assertTrue((bool)$loader->getItemPath('OtherClassA'));

        $loader->popManifest();
        $loader->pushManifest($this->testManifest2, false);
        $I->assertTrue((bool)$loader->getItemPath('ClassA'));
        $I->assertTrue((bool)$loader->getItemPath('OtherClassA'));
    }

    public function testGetItemPath(UnitTester $I)
    {
        $loader = new ClassLoader();

        $loader->pushManifest($this->testManifest1);
        $I->assertEquals(
            realpath($this->baseManifest1 . '/module/classes/ClassA.php'),
            realpath($loader->getItemPath('ClassA'))
        );
        $I->assertFalse(
            $loader->getItemPath('UnknownClass')
        );
        $I->assertFalse(
            $loader->getItemPath('OtherClassA')
        );

        $loader->pushManifest($this->testManifest2);
        $I->assertFalse(
            $loader->getItemPath('ClassA')
        );
        $I->assertFalse(
            $loader->getItemPath('UnknownClass')
        );
        $I->assertEquals(
            realpath($this->baseManifest2 . '/module/classes/OtherClassA.php'),
            realpath($loader->getItemPath('OtherClassA'))
        );
    }
}
