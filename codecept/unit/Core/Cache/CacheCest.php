<?php
namespace Core\Cache;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\ApcuCacheFactory;
use SilverStripe\Core\Cache\MemcachedCacheFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Tests\Cache\CacheTest\MockCache;
use Symfony\Component\Cache\Simple\ApcuCache;
use Symfony\Component\Cache\Simple\MemcachedCache;
use \UnitTester;

class CacheCest
{
    public function _before(UnitTester $I)
    {
        Injector::inst()
            ->load([
                ApcuCacheFactory::class => [
                    'constructor' => [ 'version' => 'ss40test' ]
                ],
                MemcachedCacheFactory::class => MemcachedCacheFactory::class,
                CacheInterface::class . '.TestApcuCache' =>  [
                    'factory' => ApcuCacheFactory::class,
                    'constructor' => [
                        'namespace' => 'TestApcuCache',
                        'defaultLifetime' => 2600,
                    ],
                ],
                CacheInterface::class . '.TestMemcache' => [
                    'factory' => MemcachedCacheFactory::class,
                    'constructor' => [
                        'namespace' => 'TestMemCache',
                        'defaultLifetime' => 5600,
                    ],
                ],
                ApcuCache::class => MockCache::class,
                MemcachedCache::class => MockCache::class,
            ]);
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testApcuCacheFactory(UnitTester $I)
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.TestApcuCache');
        $I->assertInstanceOf(
            MockCache::class,
            $cache
        );
        $I->assertEquals(
            [
                'TestApcuCache_' . md5(BASE_PATH),
                2600,
                'ss40test'
            ],
            $cache->getArgs()
        );
    }

    public function testMemCacheFactory(UnitTester $I)
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.TestMemcache');
        $I->assertInstanceOf(
            MockCache::class,
            $cache
        );
        $I->assertEquals(
            [
                null,
                'TestMemCache_' . md5(BASE_PATH),
                5600
            ],
            $cache->getArgs()
        );
    }
}
