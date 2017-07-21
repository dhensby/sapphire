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
                'silverstripe/root-module',
                'module',
                'silverstripe/awesome-module',
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

    /*
     * Note: Tests experimental API
     * @internal
     */
    public function testGetResourcePath(UnitTester $I)
    {
        $module = $this->manifest->getModule('moduleb');
        $I->assertTrue($module->hasResource('composer.json'));
        $I->assertFalse($module->hasResource('package.json'));
        $I->assertEquals(
            'moduleb/composer.json',
            $module->getRelativeResourcePath('composer.json')
        );
    }

    /*
     * Note: Tests experimental API
     * @internal
     */
    public function testGetResourcePathOnRoot(UnitTester $I)
    {
        $module = $this->manifest->getModule('silverstripe/root-module');
        $I->assertTrue($module->hasResource('composer.json'));
        $I->assertEquals(
            'composer.json',
            $module->getRelativeResourcePath('composer.json')
        );
    }
}
