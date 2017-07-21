<?php
namespace Core\Manifest;
use Codeception\Example;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;
use \UnitTester;

class NamespacedClassManifestCest
{
    /**
     * @var string
     */
    protected $base;

    /**
     * @var ClassManifest
     */
    protected $manifest;

    public function _before(UnitTester $I)
    {
        $this->base = FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/namespaced_classmanifest';
        $this->manifest = new ClassManifest($this->base);
        $this->manifest->init();
        ClassLoader::inst()->pushManifest($this->manifest, false);
    }

    public function _after(UnitTester $I)
    {
        ClassLoader::inst()->popManifest();
    }

    // tests
    public function testClassInfoIsCorrect(UnitTester $I)
    {
        $I->assertContains(
            'SilverStripe\Framework\Tests\ClassI',
            ClassInfo::implementorsOf(PermissionProvider::class)
        );

        // because we're using a nested manifest we have to "coalesce" the descendants again to correctly populate the
        // descendants of the core classes we want to test against - this is a limitation of the test manifest not
        // including all core classes
        $method = new \ReflectionMethod($this->manifest, 'coalesceDescendants');
        $method->setAccessible(true);
        $method->invoke($this->manifest, DataObject::class);

        $I->assertContains(
            'SilverStripe\Framework\Tests\ClassI',
            ClassInfo::subclassesFor(DataObject::class)
        );
    }

    /**
     * @example [ "SILVERSTRIPE\\TEST\\CLASSA", "module/classes/ClassA.php" ]
     * @example [ "Silverstripe\\Test\\ClassA", "module/classes/ClassA.php" ]
     * @example [ "silverstripe\\test\\classa", "module/classes/ClassA.php" ]
     * @example [ "SILVERSTRIPE\\TEST\\INTERFACEA", "module/interfaces/InterfaceA.php" ]
     * @example [ "Silverstripe\\Test\\InterfaceA", "module/interfaces/InterfaceA.php" ]
     * @example [ "silverstripe\\test\\interfacea", "module/interfaces/InterfaceA.php" ]
     */
    public function testGetItemPath(UnitTester $I, Example $example)
    {
        list($name, $path) = $example;

        $I->assertEquals("{$this->base}/$path", $this->manifest->getItemPath($name));
    }

    public function testGetClasses(UnitTester $I)
    {
        $expect = array(
            'silverstripe\\test\\classa' => "{$this->base}/module/classes/ClassA.php",
            'silverstripe\\test\\classb' => "{$this->base}/module/classes/ClassB.php",
            'silverstripe\\test\\classc' => "{$this->base}/module/classes/ClassC.php",
            'silverstripe\\test\\classd' => "{$this->base}/module/classes/ClassD.php",
            'silverstripe\\test\\classe' => "{$this->base}/module/classes/ClassE.php",
            'silverstripe\\test\\classf' => "{$this->base}/module/classes/ClassF.php",
            'silverstripe\\test\\classg' => "{$this->base}/module/classes/ClassG.php",
            'silverstripe\\test\\classh' => "{$this->base}/module/classes/ClassH.php",
            'silverstripe\\framework\\tests\\classi' => "{$this->base}/module/classes/ClassI.php",
        );

        $I->assertEquals($expect, $this->manifest->getClasses());
    }

    public function testGetClassNames(UnitTester $I)
    {
        $I->assertEquals(
            [
                'silverstripe\test\classa' => 'silverstripe\test\ClassA',
                'silverstripe\test\classb' => 'silverstripe\test\ClassB',
                'silverstripe\test\classc' => 'silverstripe\test\ClassC',
                'silverstripe\test\classd' => 'silverstripe\test\ClassD',
                'silverstripe\test\classe' => 'silverstripe\test\ClassE',
                'silverstripe\test\classf' => 'silverstripe\test\ClassF',
                'silverstripe\test\classg' => 'silverstripe\test\ClassG',
                'silverstripe\test\classh' => 'silverstripe\test\ClassH',
                'silverstripe\framework\tests\classi' => 'SilverStripe\Framework\Tests\ClassI',
            ],
            $this->manifest->getClassNames()
        );
    }

    public function testGetDescendants(UnitTester $I)
    {
        $expect = [
            'silverstripe\\test\\classa' => [
                'silverstripe\\test\\classb' => 'silverstripe\\test\\ClassB',
                'silverstripe\\test\\classh' => 'silverstripe\\test\\ClassH',
            ],
        ];

        $I->assertEquals($expect, $this->manifest->getDescendants());
    }

    public function testGetDescendantsOf(UnitTester $I)
    {
        $expect = [
            'SILVERSTRIPE\\TEST\\CLASSA' => [
                'silverstripe\\test\\classb' => 'silverstripe\\test\\ClassB',
                'silverstripe\\test\\classh' => 'silverstripe\\test\\ClassH',
            ],
            'silverstripe\\test\\classa' => [
                'silverstripe\\test\\classb' => 'silverstripe\\test\\ClassB',
                'silverstripe\\test\\classh' => 'silverstripe\\test\\ClassH',
            ],
        ];

        foreach ($expect as $class => $desc) {
            $I->assertEquals($desc, $this->manifest->getDescendantsOf($class));
        }
    }

    public function testGetInterfaces(UnitTester $I)
    {
        $expect = array(
            'silverstripe\\test\\interfacea' => "{$this->base}/module/interfaces/InterfaceA.php",
        );
        $I->assertEquals($expect, $this->manifest->getInterfaces());
    }

    public function testGetImplementors(UnitTester $I)
    {
        $expect = [
            'silverstripe\\test\\interfacea' => [
                'silverstripe\\test\\classe' => 'silverstripe\\test\\ClassE'
            ],
            'interfacea' => [
                'silverstripe\\test\\classf' => 'silverstripe\\test\\ClassF'
            ],
            'silverstripe\\test\\subtest\\interfacea' => [
                'silverstripe\\test\\classg' => 'silverstripe\\test\\ClassG'
            ],
            'silverstripe\\security\\permissionprovider' => [
                'silverstripe\\framework\\tests\\classi' => 'SilverStripe\\Framework\\Tests\\ClassI'
            ],
        ];
        $I->assertEquals($expect, $this->manifest->getImplementors());
    }

    /**
     * @example [ "SILVERSTRIPE\\TEST\\INTERFACEA", { "silverstripe\\test\\classe": "silverstripe\\test\\ClassE" } ]
     * @example [ "silverstripe\\test\\interfacea", { "silverstripe\\test\\classe": "silverstripe\\test\\ClassE" } ]
     * @example [ "INTERFACEA", { "silverstripe\\test\\classf": "silverstripe\\test\\ClassF" } ]
     * @example [ "interfacea", { "silverstripe\\test\\classf": "silverstripe\\test\\ClassF" } ]
     * @example [ "SILVERSTRIPE\\TEST\\SUBTEST\\INTERFACEA", { "silverstripe\\test\\classg": "silverstripe\\test\\ClassG" } ]
     * @example [ "silverstripe\\test\\subtest\\interfacea", { "silverstripe\\test\\classg": "silverstripe\\test\\ClassG" } ]
     */
    public function testGetImplementorsOf(UnitTester $I, Example $example)
    {
        list($interface, $implementor) = $example;
        $I->assertEquals($implementor, $this->manifest->getImplementorsOf($interface));
    }
}
