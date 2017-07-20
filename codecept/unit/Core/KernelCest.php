<?php
namespace Core;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Kernel;
use \UnitTester;

class KernelCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testNesting(UnitTester $I)
    {
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);

        /** @var CoreKernel $nested1 */
        $nested1 = $kernel->nest();
        Director::config()->set('alternate_base_url', '/mysite/');
        $I->assertEquals($kernel, $nested1->getNestedFrom());
        $I->assertEquals($nested1->getConfigLoader(), ConfigLoader::inst());
        $I->assertEquals($nested1->getInjectorLoader(), InjectorLoader::inst());
        $I->assertEquals(1, ConfigLoader::inst()->countManifests());
        $I->assertEquals(1, InjectorLoader::inst()->countManifests());

        // Re-nest
        $nested2 = $nested1->nest();

        // Nesting config / injector should increase this count
        Injector::nest();
        Config::nest();
        $I->assertEquals($nested2->getConfigLoader(), ConfigLoader::inst());
        $I->assertEquals($nested2->getInjectorLoader(), InjectorLoader::inst());
        $I->assertEquals(2, ConfigLoader::inst()->countManifests());
        $I->assertEquals(2, InjectorLoader::inst()->countManifests());
        Director::config()->set('alternate_base_url', '/anothersite/');

        // Nesting always resets sub-loaders to 1
        $nested2->nest();
        $I->assertEquals(1, ConfigLoader::inst()->countManifests());
        $I->assertEquals(1, InjectorLoader::inst()->countManifests());

        // Calling ->activate() on a previous kernel restores
        $nested1->activate();
        $I->assertEquals($nested1->getConfigLoader(), ConfigLoader::inst());
        $I->assertEquals($nested1->getInjectorLoader(), InjectorLoader::inst());
        $I->assertEquals('/mysite/', Director::config()->get('alternate_base_url'));
        $I->assertEquals(1, ConfigLoader::inst()->countManifests());
        $I->assertEquals(1, InjectorLoader::inst()->countManifests());
    }

    public function testInvalidInjectorDetection(UnitTester $I)
    {
        $I->expectException(\BadMethodCallException::class, function () {
            /** @var Kernel $kernel */
            $kernel = Injector::inst()->get(Kernel::class);
            $kernel->nest(); // $kernel is no longer current kernel

            $kernel->getInjectorLoader()->getManifest();
        });
    }

    public function testInvalidConfigDetection(UnitTester $I)
    {
        $I->expectException(\BadMethodCallException::class, function () {
            /** @var Kernel $kernel */
            $kernel = Injector::inst()->get(Kernel::class);
            $kernel->nest(); // $kernel is no longer current kernel

            $kernel->getConfigLoader()->getManifest();
        });
    }
}
