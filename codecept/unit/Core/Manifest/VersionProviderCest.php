<?php
namespace Core\Manifest;
use Codeception\Stub\Expected;
use Codeception\Util\Stub;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\VersionProvider;
use \UnitTester;

class VersionProviderCest
{
    /**
     * @var VersionProvider
     */
    protected $provider;

    public function _before(UnitTester $I)
    {
        $this->provider = new VersionProvider;
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testGetModules(UnitTester $I)
    {
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/somepackage' => 'Some Package',
            'silverstripe/hidden' => '',
            'silverstripe/another' => 'Another'
        ]);

        $result = $this->provider->getModules();
        $I->assertArrayHasKey('silverstripe/somepackage', $result);
        $I->assertSame('Some Package', $result['silverstripe/somepackage']);
        $I->assertArrayHasKey('silverstripe/another', $result);
        $I->assertArrayNotHasKey('silverstripe/hidden', $result);
    }

    public function testGetModuleVersionFromComposer(UnitTester $I)
    {
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/framework' => 'Framework',
            'silverstripe/siteconfig' => 'SiteConfig'
        ]);

        $result = $this->provider->getModules(['silverstripe/framework']);
        $I->assertArrayHasKey('silverstripe/framework', $result);
        $I->assertNotEmpty($result['silverstripe/framework']);
    }

    public function testGetVersion(UnitTester $I)
    {
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/framework' => 'Framework',
            'silverstripe/siteconfig' => 'SiteConfig'
        ]);

        $result = $this->provider->getVersion();
        $I->assertContains('SiteConfig: ', $result);
        $I->assertContains('Framework: ', $result);
        $I->assertContains(', ', $result);
    }

    public function testGetModulesFromComposerLock(UnitTester $I)
    {
        $mock = Stub::make(VersionProvider::class, [
            'getComposerLock' => Expected::once(function () {
                return [
                    'packages' => [
                        [
                            'name' => 'silverstripe/somepackage',
                            'version' => '1.2.3'
                        ],
                        [
                            'name' => 'silverstripe/another',
                            'version' => '2.3.4'
                        ]
                    ]
                ];
            }),
        ]);

        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/somepackage' => 'Some Package'
        ]);

        $result = $mock->getVersion();
        $I->assertContains('Some Package: 1.2.3', $result);
    }
}
