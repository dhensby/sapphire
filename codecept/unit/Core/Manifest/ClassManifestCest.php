<?php
namespace Core\Manifest;

use Codeception\Example;
use SilverStripe\Core\Manifest\ClassManifest;
use \UnitTester;

class ClassManifestCest
{
    /**
     * @var string
     */
    protected $base;

    /**
     * @var ClassManifest
     */
    protected $manifest;

    /**
     * @var ClassManifest
     */
    protected $manifestTests;

    public function _before(UnitTester $I)
    {
        $this->base = FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/classmanifest';
        $this->manifest = new ClassManifest($this->base);
        $this->manifest->init(false);
        $this->manifestTests = new ClassManifest($this->base);
        $this->manifestTests->init(true);
    }

    public function _after(UnitTester $I)
    {
    }

    // tests

    /**
     * @example ["CLASSA", "module/classes/ClassA.php"]
     * @example ["ClassA", "module/classes/ClassA.php"]
     * @example ["classa", "module/classes/ClassA.php"]
     * @example ["INTERFACEA", "module/interfaces/InterfaceA.php"]
     * @example ["InterfaceA", "module/interfaces/InterfaceA.php"]
     * @example ["interfacea", "module/interfaces/InterfaceA.php"]
     * @example ["TestTraitA", "module/traits/TestTraitA.php"]
     * @example ["TestNamespace\\Testing\\TestTraitB", "module/traits/TestTraitB.php"]
     * @example ["VendorClassA", "vendor/silverstripe/modulec/code/VendorClassA.php"]
     * @example ["VendorTraitA", "vendor/silverstripe/modulec/code/VendorTraitA.php"]
     */
    public function testGetItemPath(UnitTester $I, Example $example)
    {
        list($name, $path) = $example;
        $I->assertEquals("{$this->base}/$path", $this->manifest->getItemPath($name));
    }

    public function testGetClasses(UnitTester $I)
    {
        $expect = [
            'classa' => "{$this->base}/module/classes/ClassA.php",
            'classb' => "{$this->base}/module/classes/ClassB.php",
            'classc' => "{$this->base}/module/classes/ClassC.php",
            'classd' => "{$this->base}/module/classes/ClassD.php",
            'classe' => "{$this->base}/module/classes/ClassE.php",
            'vendorclassa' => "{$this->base}/vendor/silverstripe/modulec/code/VendorClassA.php",
        ];
        $I->assertEquals($expect, $this->manifest->getClasses());
    }

    public function testGetClassNames(UnitTester $I)
    {
        $I->assertEquals(
            [
                'classa' => 'ClassA',
                'classb' => 'ClassB',
                'classc' => 'ClassC',
                'classd' => 'ClassD',
                'classe' => 'ClassE',
                'vendorclassa' => 'VendorClassA',
            ],
            $this->manifest->getClassNames()
        );
    }

    public function testGetTraitNames(UnitTester $I)
    {
        $I->assertEquals(
            [
                'testtraita' => 'TestTraitA',
                'testnamespace\\testing\\testtraitb' => 'TestNamespace\\Testing\\TestTraitB',
                'vendortraita' => 'VendorTraitA',
            ],
            $this->manifest->getTraitNames()
        );
    }

    public function testGetDescendants(UnitTester $I)
    {
        $expect = [
            'classa' => [
                'classc' => 'ClassC',
                'classd' => 'ClassD',
            ],
            'classc' => [
                'classd' => 'ClassD',
            ],
        ];
        $I->assertEquals($expect, $this->manifest->getDescendants());
    }

    public function testGetDescendantsOf(UnitTester $I)
    {
        $expect = [
            'CLASSA' => ['classc' => 'ClassC', 'classd' => 'ClassD'],
            'classa' => ['classc' => 'ClassC', 'classd' => 'ClassD'],
            'CLASSC' => ['classd' => 'ClassD'],
            'classc' => ['classd' => 'ClassD'],
        ];

        foreach ($expect as $class => $desc) {
            $I->assertEquals($desc, $this->manifest->getDescendantsOf($class));
        }
    }

    public function testGetInterfaces(UnitTester $I)
    {
        $expect = array(
            'interfacea' => "{$this->base}/module/interfaces/InterfaceA.php",
            'interfaceb' => "{$this->base}/module/interfaces/InterfaceB.php"
        );
        $I->assertEquals($expect, $this->manifest->getInterfaces());
    }

    public function testGetImplementors(UnitTester $I)
    {
        $expect = [
            'interfacea' => ['classb' => 'ClassB'],
            'interfaceb' => ['classc' => 'ClassC'],
        ];
        $I->assertEquals($expect, $this->manifest->getImplementors());
    }

    public function testGetImplementorsOf(UnitTester $I)
    {
        $expect = [
            'INTERFACEA' => ['classb' => 'ClassB'],
            'interfacea' => ['classb' => 'ClassB'],
            'INTERFACEB' => ['classc' => 'ClassC'],
            'interfaceb' => ['classc' => 'ClassC'],
        ];

        foreach ($expect as $interface => $impl) {
            $I->assertEquals($impl, $this->manifest->getImplementorsOf($interface));
        }
    }

    public function testTestManifestIncludesTestClasses(UnitTester $I)
    {
        $I->assertArrayNotHasKey('testclassa', $this->manifest->getClasses());
        $I->assertArrayHasKey('testclassa', $this->manifestTests->getClasses());
    }

    public function testManifestExcludeFilesPrefixedWithUnderscore(UnitTester $I)
    {
        $I->assertArrayNotHasKey('ignore', $this->manifest->getClasses());
    }

    /**
     * Assert that ClassManifest throws an exception when it encounters two files
     * which contain classes with the same name
     */
    public function testManifestWarnsAboutDuplicateClasses(UnitTester $I)
    {
        $I->expectException(\Exception::class, function () {
            $manifest = new ClassManifest(FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/classmanifest_duplicates');
            $manifest->init();
        });
    }
}
