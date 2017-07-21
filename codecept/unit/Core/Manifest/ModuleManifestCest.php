<?php
namespace Core\Manifest;
use SilverStripe\Core\Manifest\ModuleManifest;
use \UnitTester;

class ModuleManifestCest
{
    /**
     * @var string
     */
    protected $base;

    /**
     * @var ModuleManifest
     */
    protected $manifest;

    public function _before(UnitTester $I)
    {
        $this->base = FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/classmanifest';
        $this->manifest = new ModuleManifest($this->base);
        $this->manifest->init();
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testGetModules(UnitTester $I)
    {
        $modules = $this->manifest->getModules();
        $I->assertEquals(
            [
                'module',
                'silverstripe/awesome-module',
                'silverstripe/modulec',
                'silverstripe/root-module',
            ],
            array_keys($modules)
        );
    }

    public function testGetLegacyModule(UnitTester $I)
    {
        $module = $this->manifest->getModule('module');
        $I->assertNotEmpty($module);
        $I->assertEquals('module', $module->getName());
        $I->assertEquals('module', $module->getShortName());
        $I->assertEquals('module', $module->getRelativePath());
        $I->assertEmpty($module->getComposerName());
    }

    public function testGetComposerModule(UnitTester $I)
    {
        // Get by installer-name (folder)
        $moduleByShortName = $this->manifest->getModule('moduleb');
        $I->assertNotEmpty($moduleByShortName);

        // Can also get this by full composer name
        $module = $this->manifest->getModule('silverstripe/awesome-module');
        $I->assertNotEmpty($module);
        $I->assertEquals($moduleByShortName->getPath(), $module->getPath());

        // correctly respects vendor
        $I->assertEmpty($this->manifest->getModule('wrongvendor/awesome-module'));
        $I->assertEmpty($this->manifest->getModule('wrongvendor/moduleb'));

        // Properties of module
        $I->assertEquals('silverstripe/awesome-module', $module->getName());
        $I->assertEquals('silverstripe/awesome-module', $module->getComposerName());
        $I->assertEquals('moduleb', $module->getShortName());
        $I->assertEquals('moduleb', $module->getRelativePath());
    }

    public function testGetResourcePath(UnitTester $I)
    {
        // Root module
        $moduleb = $this->manifest->getModule('moduleb');
        $I->assertTrue($moduleb->getResource('composer.json')->exists());
        $I->assertFalse($moduleb->getResource('package.json')->exists());
        $I->assertEquals(
            'moduleb/composer.json',
            $moduleb->getResource('composer.json')->getRelativePath()
        );
    }

    public function testGetResourcePathsInVendor(UnitTester $I)
    {
        // Vendor module
        $modulec = $this->manifest->getModule('silverstripe/modulec');
        $I->assertTrue($modulec->getResource('composer.json')->exists());
        $I->assertFalse($modulec->getResource('package.json')->exists());
        $I->assertEquals(
            'vendor/silverstripe/modulec/composer.json',
            $modulec->getResource('composer.json')->getRelativePath()
        );
    }

    public function testGetResourcePathOnRoot(UnitTester $I)
    {
        $module = $this->manifest->getModule('silverstripe/root-module');
        $I->assertTrue($module->getResource('composer.json')->exists());
        $I->assertEquals(
            'composer.json',
            $module->getResource('composer.json')->getRelativePath()
        );
    }
}
