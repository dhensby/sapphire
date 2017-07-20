<?php
namespace Core\Manifest;
use Dotenv\Loader;
use SilverStripe\Config\Collections\MemoryConfigCollection;
use SilverStripe\Core\Config\CoreConfigFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleManifest;
use \UnitTester;

class ConfigManifestCest
{
    public function _before(UnitTester $I)
    {
        $moduleManifest = new ModuleManifest(FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/configmanifest');
        $moduleManifest->init();
        ModuleLoader::inst()->pushManifest($moduleManifest);
    }

    public function _after(UnitTester $I)
    {
        ModuleLoader::inst()->popManifest();
    }

    /**
     * This is a helper method for getting a new manifest
     *
     * @param string $name
     * @return mixed
     */
    protected function getConfigFixtureValue($name)
    {
        return $this->getTestConfig()->get('SilverStripe\Core\Tests\Manifest\ConfigManifestTest', $name);
    }

    /**
     * Build a new config based on YMl manifest
     *
     * @return MemoryConfigCollection
     */
    protected function getTestConfig()
    {
        $config = new MemoryConfigCollection();
        $factory = new CoreConfigFactory();
        $transformer = $factory->buildYamlTransformerForPath(FRAMEWORK_PATH . '/tests/php/Core/Manifest/fixtures/configmanifest');
        $config->transform([$transformer]);
        return $config;
    }

    /**
     * This is a helper method for displaying a relevant message about a parsing failure
     *
     * @param string $path
     * @return string
     */
    protected function getParsedAsMessage($path)
    {
        return sprintf('Reference path "%s" failed to parse correctly', $path);
    }

    // tests
    public function testClassRules(UnitTester $I)
    {
        $config = $this->getConfigFixtureValue('Class');
codecept_debug($config);
        $I->assertArrayHasKey('DirectorExists', $config);
        $I->assertEquals(
            'Yes',
            $config['DirectorExists'],
            'Only rule correctly detects existing class'
        );

        $I->assertArrayHasKey('NoSuchClassExists', $config);
        $I->assertEquals(
            'No',
            $config['NoSuchClassExists'],
            'Except rule correctly detects missing class'
        );
    }

    public function testModuleRules(UnitTester $I)
    {
        $config = $this->getConfigFixtureValue('Module');

        $I->assertArrayHasKey('MysiteExists', $config);
        $I->assertEquals(
            'Yes',
            $config['MysiteExists'],
            'Only rule correctly detects existing module'
        );

        $I->assertArrayHasKey('NoSuchModuleExists', $config);
        $I->assertEquals(
            'No',
            $config['NoSuchModuleExists'],
            'Except rule correctly detects missing module'
        );
    }

    public function testEnvVarSetRules(UnitTester $I)
    {
        $loader = new Loader(null);
        $loader->setEnvironmentVariable('ENVVARSET_FOO', 1);
        $config = $this->getConfigFixtureValue('EnvVarSet');

        $I->assertArrayHasKey('FooSet', $config);
        $I->assertEquals(
            'Yes',
            $config['FooSet'],
            'Only rule correctly detects set environment variable'
        );

        $I->assertArrayHasKey('BarSet', $config);
        $I->assertEquals(
            'No',
            $config['BarSet'],
            'Except rule correctly detects unset environment variable'
        );
    }

    public function testConstantDefinedRules(UnitTester $I)
    {
        define('CONSTANTDEFINED_FOO', 1);
        $config = $this->getConfigFixtureValue('ConstantDefined');

        $I->assertArrayHasKey('FooDefined', $config);
        $I->assertEquals(
            'Yes',
            $config['FooDefined'],
            'Only rule correctly detects defined constant'
        );

        $I->assertArrayHasKey('BarDefined', $config);
        $I->assertEquals(
            'No',
            $config['BarDefined'],
            'Except rule correctly detects undefined constant'
        );
    }

    public function testEnvOrConstantMatchesValueRules(UnitTester $I)
    {
        $loader = new Loader(null);

        $loader->setEnvironmentVariable('CONSTANTMATCHESVALUE_FOO', 'Foo');
        define('CONSTANTMATCHESVALUE_BAR', 'Bar');
        $config = $this->getConfigFixtureValue('EnvOrConstantMatchesValue');

        $I->assertArrayHasKey('FooIsFoo', $config);
        $I->assertEquals(
            'Yes',
            $config['FooIsFoo'],
            'Only rule correctly detects environment variable matches specified value'
        );

        $I->assertArrayHasKey('BarIsBar', $config);
        $I->assertEquals(
            'Yes',
            $config['BarIsBar'],
            'Only rule correctly detects constant matches specified value'
        );

        $I->assertArrayHasKey('FooIsQux', $config);
        $I->assertEquals(
            'No',
            $config['FooIsQux'],
            'Except rule correctly detects environment variable that doesn\'t match specified value'
        );

        $I->assertArrayHasKey('BarIsQux', $config);
        $I->assertEquals(
            'No',
            $config['BarIsQux'],
            'Except rule correctly detects environment variable that doesn\'t match specified value'
        );

        $I->assertArrayHasKey('BazIsBaz', $config);
        $I->assertEquals(
            'No',
            $config['BazIsBaz'],
            'Except rule correctly detects undefined variable'
        );
    }

    public function testEnvironmentRules(UnitTester $I)
    {
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        foreach (array('dev', 'test', 'live') as $env) {
            $kernel->setEnvironment($env);
            $config = $this->getConfigFixtureValue('Environment');

            foreach (array('dev', 'test', 'live') as $check) {
                $I->assertArrayHasKey(ucfirst($check).'Environment', $config);
                $I->assertEquals(
                    $env == $check ? $check : 'not'.$check,
                    $config[ucfirst($check).'Environment'],
                    'Only & except rules correctly detect environment in env ' . $env
                );
            }
        }
    }

    public function testMultipleRules(UnitTester $I)
    {
        $loader = new Loader(null);

        $loader->setEnvironmentVariable('MULTIPLERULES_ENVVARIABLESET', 1);
        define('MULTIPLERULES_DEFINEDCONSTANT', 'defined');
        $config = $this->getConfigFixtureValue('MultipleRules');

        $I->assertArrayNotHasKey(
            'TwoOnlyFail',
            $config,
            'Fragment is not included if one of the Only rules fails.'
        );

        $I->assertArrayHasKey(
            'TwoOnlySucceed',
            $config,
            'Fragment is included if both Only rules succeed.'
        );

        $I->assertArrayNotHasKey(
            'OneExceptFail',
            $config,
            'Fragment is included if one of the Except rules matches.'
        );

        $I->assertArrayNotHasKey(
            'TwoExceptFail',
            $config,
            'Fragment is not included if both of the Except rules fail.'
        );

        $I->assertArrayNotHasKey(
            'TwoBlocksFail',
            $config,
            'Fragment is not included if one block fails.'
        );

        $I->assertArrayHasKey(
            'TwoBlocksSucceed',
            $config,
            'Fragment is included if both blocks succeed.'
        );
    }
}
