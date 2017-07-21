<?php
namespace Core\Manifest;
use SilverStripe\Core\Manifest\ManifestFileFinder;
use \UnitTester;

class ManifestFileFinderCest
{
    /**
     * @var string
     */
    protected $defaultBase;

    public function _before(UnitTester $I)
    {
        $this->defaultBase = FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/manifestfilefinder';
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testBasicOperation(UnitTester $I)
    {
        $finder = new ManifestFileFinder();
        $finder->setOption('name_regex', '/\.txt$/');
        $found = $finder->find($this->defaultBase);

        $I->assertCount(1, $found);
        $I->assertContains($this->defaultBase . '/module/module.txt', $found);
    }

    public function testIgnoreTests(UnitTester $I)
    {
        $finder = new ManifestFileFinder();
        $finder->setOption('name_regex', '/\.txt$/');
        $finder->setOption('ignore_tests', false);
        $found = $finder->find($this->defaultBase);

        $I->assertCount(3, $found);
        $I->assertContains($this->defaultBase . '/module/module.txt', $found);
        $I->assertContains($this->defaultBase . '/module/tests/tests.txt', $found);
        $I->assertContains($this->defaultBase . '/module/code/tests/tests2.txt', $found);
    }

    public function testIncludeThemes(UnitTester $I)
    {
        $finder = new ManifestFileFinder();
        $finder->setOption('name_regex', '/\.txt$/');
        $finder->setOption('include_themes', true);
        $found = $finder->find($this->defaultBase);

        $I->assertCount(2, $found);
        $I->assertContains($this->defaultBase . '/module/module.txt', $found);
        $I->assertContains($this->defaultBase . '/themes/themes.txt', $found);
    }

    public function testIncludeWithRootConfigFile(UnitTester $I)
    {
        $finder = new ManifestFileFinder();
        $base = FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/manifestfilefinder_rootconfigfile';
        $found = $finder->find($base);

        $I->assertCount(1, $found);
        $I->assertContains($base . '/code/code.txt', $found);
    }

    public function testIncludeWithRootConfigFolder(UnitTester $I)
    {
        $finder = new ManifestFileFinder();
        $base = FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/manifestfilefinder_rootconfigfolder';
        $found = $finder->find($base);

        $I->assertCount(2, $found);
        $I->assertContains($base . '/_config/config.yml', $found);
        $I->assertContains($base . '/code/code.txt', $found);
    }
}
