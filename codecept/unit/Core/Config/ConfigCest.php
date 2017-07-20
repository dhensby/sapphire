<?php
namespace Core\Config;
use SilverStripe\Config\MergeStrategy\Priority;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Tests\Config\ConfigTest\Combined3;
use SilverStripe\Core\Tests\Config\ConfigTest\DefinesFoo;
use SilverStripe\Core\Tests\Config\ConfigTest\First;
use SilverStripe\Core\Tests\Config\ConfigTest\Fourth;
use SilverStripe\Core\Tests\Config\ConfigTest\Second;
use SilverStripe\Core\Tests\Config\ConfigTest\TestNest;
use SilverStripe\Core\Tests\Config\ConfigTest\Third;
use \UnitTester;

class ConfigCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testNest(UnitTester $I)
    {
        // Check basic config
        $I->assertEquals(3, Config::inst()->get(TestNest::class, 'foo'));
        $I->assertEquals(5, Config::inst()->get(TestNest::class, 'bar'));

        // Test nest copies data
        Config::nest();
        $I->assertEquals(3, Config::inst()->get(TestNest::class, 'foo'));
        $I->assertEquals(5, Config::inst()->get(TestNest::class, 'bar'));

        // Test nested data can be updated
        Config::modify()->merge(TestNest::class, 'foo', 4);
        $I->assertEquals(4, Config::inst()->get(TestNest::class, 'foo'));
        $I->assertEquals(5, Config::inst()->get(TestNest::class, 'bar'));

        // Test unnest restores data
        Config::unnest();
        $I->assertEquals(3, Config::inst()->get(TestNest::class, 'foo'));
        $I->assertEquals(5, Config::inst()->get(TestNest::class, 'bar'));
    }

    public function testUpdateStatic(UnitTester $I)
    {
        // Test base state
        $I->assertEquals(
            ['test_1'],
            Config::inst()->get(First::class, 'first')
        );
        $I->assertEquals(
            [
                'test_1',
                'test_2'
            ],
            Config::inst()->get(Second::class, 'first')
        );
        $I->assertEquals(
            [ 'test_2' ],
            Config::inst()->get(Second::class, 'first', Config::UNINHERITED)
        );
        $I->assertEquals(
            [
                'test_1',
                'test_2',
                'test_3'
            ],
            Config::inst()->get(Third::class, 'first')
        );
        $I->assertEquals(
            [ 'test_3' ],
            Config::inst()->get(Third::class, 'first', true)
        );

        // Modify first param
        Config::modify()->merge(First::class, 'first', array('test_1_2'));
        Config::modify()->merge(Third::class, 'first', array('test_3_2'));
        Config::modify()->merge(Fourth::class, 'first', array('test_4'));

        // Check base class
        $I->assertEquals(
            ['test_1', 'test_1_2'],
            Config::inst()->get(First::class, 'first')
        );
        $I->assertEquals(
            ['test_1', 'test_1_2'],
            Config::inst()->get(First::class, 'first', Config::UNINHERITED)
        );
        $I->assertEquals(
            ['test_1'],
            Config::inst()->get(First::class, 'first', Config::NO_DELTAS)
        );
        $I->assertEquals(
            ['test_1'],
            Config::inst()->get(First::class, 'first', Config::NO_DELTAS | Config::UNINHERITED)
        );

        // Modify second param
        Config::modify()->merge(Fourth::class, 'second', array('test_4'));
        Config::modify()->merge(Third::class, 'second', array('test_3_2'));

        // Check fourth class
        $I->assertEquals(
            ['test_1', 'test_3', 'test_3_2', 'test_4'],
            Config::inst()->get(Fourth::class, 'second')
        );
        $I->assertEquals(
            ['test_4'],
            Config::inst()->get(Fourth::class, 'second', Config::UNINHERITED)
        );
        $I->assertEquals(
            ['test_1', 'test_3'],
            Config::inst()->get(Fourth::class, 'second', Config::NO_DELTAS)
        );
        $I->assertNull(
            Config::inst()->get(Fourth::class, 'second', Config::NO_DELTAS | Config::UNINHERITED)
        );

        // Check third class
        $I->assertEquals(
            ['test_1', 'test_3', 'test_3_2'],
            Config::inst()->get(Third::class, 'second')
        );
        $I->assertEquals(
            ['test_3', 'test_3_2'],
            Config::inst()->get(Third::class, 'second', Config::UNINHERITED)
        );
        $I->assertEquals(
            ['test_1', 'test_3'],
            Config::inst()->get(Third::class, 'second', Config::NO_DELTAS)
        );
        $I->assertEquals(
            ['test_3'],
            Config::inst()->get(Third::class, 'second', Config::NO_DELTAS | Config::UNINHERITED)
        );

        // Test remove()
        Config::modify()->remove(Third::class, 'second');

        // Check third class ->get()
        $I->assertNull(
            Config::inst()->get(Third::class, 'second')
        );
        $I->assertEquals(
            ['test_1', 'test_3'],
            Config::inst()->get(Third::class, 'second', Config::NO_DELTAS)
        );
        $I->assertNull(
            Config::inst()->get(Third::class, 'second', Config::UNINHERITED)
        );

        // Check ->exists()
        $I->assertFalse(
            Config::inst()->exists(Third::class, 'second')
        );
        $I->assertFalse(
            Config::inst()->exists(Third::class, 'second', Config::UNINHERITED)
        );
        $I->assertTrue(
            Config::inst()->exists(Third::class, 'second', Config::NO_DELTAS)
        );

        // Test merge()
        Config::modify()->merge(Third::class, 'second', ['test_3_2']);
        $I->assertEquals(
            ['test_3_2'],
            Config::inst()->get(Third::class, 'second')
        );
        // No-deltas omits both above ->remove() as well as ->merge()
        $I->assertEquals(
            ['test_1', 'test_3'],
            Config::inst()->get(Third::class, 'second', Config::NO_DELTAS)
        );
    }


    public function testUpdateWithFalsyValues(UnitTester $I)
    {
        // Booleans
        $I->assertTrue(Config::inst()->get(First::class, 'bool'));
        Config::modify()->merge(First::class, 'bool', false);
        $I->assertFalse(Config::inst()->get(First::class, 'bool'));
        Config::modify()->merge(First::class, 'bool', true);
        $I->assertTrue(Config::inst()->get(First::class, 'bool'));

        // Integers
        $I->assertEquals(42, Config::inst()->get(First::class, 'int'));
        Config::modify()->merge(First::class, 'int', 0);
        $I->assertEquals(0, Config::inst()->get(First::class, 'int'));
        Config::modify()->merge(First::class, 'int', 42);
        $I->assertEquals(42, Config::inst()->get(First::class, 'int'));

        // Strings
        $I->assertEquals('value', Config::inst()->get(First::class, 'string'));
        Config::modify()->merge(First::class, 'string', '');
        $I->assertEquals('', Config::inst()->get(First::class, 'string'));
        Config::modify()->merge(First::class, 'string', 'value');
        $I->assertEquals('value', Config::inst()->get(First::class, 'string'));

        // Nulls
        $I->assertEquals('value', Config::inst()->get(First::class, 'nullable'));
        Config::modify()->merge(First::class, 'nullable', null);
        $I->assertNull(Config::inst()->get(First::class, 'nullable'));
        Config::modify()->merge(First::class, 'nullable', 'value');
        $I->assertEquals('value', Config::inst()->get(First::class, 'nullable'));
    }

    public function testSetsFalsyDefaults(UnitTester $I)
    {
        $I->assertFalse(Config::inst()->get(First::class, 'default_false'));
        // Technically the same as an undefined config key
        $I->assertNull(Config::inst()->get(First::class, 'default_null'));
        $I->assertEquals(0, Config::inst()->get(First::class, 'default_zero'));
        $I->assertEquals('', Config::inst()->get(First::class, 'default_empty_string'));
    }

    public function testUninheritedStatic(UnitTester $I)
    {
        $I->assertEquals(
            'test_1',
            Config::inst()->get(First::class, 'third', Config::UNINHERITED));
        $I->assertNull(Config::inst()->get(Fourth::class, 'third', Config::UNINHERITED));

        Config::modify()->merge(First::class, 'first', array('test_1b'));
        Config::modify()->merge(Second::class, 'first', array('test_2b'));

        // Check that it can be applied to parent and subclasses, and queried directly
        $I->assertContains(
            'test_1b',
            Config::inst()->get(First::class, 'first', Config::UNINHERITED)
        );
        $I->assertContains(
            'test_2b',
            Config::inst()->get(Second::class, 'first', Config::UNINHERITED)
        );

        // But it won't affect subclasses - this is *uninherited* static
        $I->assertNotContains(
            'test_2b',
            Config::inst()->get(Third::class, 'first', Config::UNINHERITED)
        );
        $I->assertNull(Config::inst()->get(Fourth::class, 'first', Config::UNINHERITED));

        // Subclasses that don't have the static explicitly defined should allow definition, also
        // This also checks that set can be called after the first uninherited get()
        // call (which can be buggy due to caching)
        Config::modify()->merge(Fourth::class, 'first', array('test_4b'));
        $I->assertContains('test_4b', Config::inst()->get(Fourth::class, 'first', Config::UNINHERITED));
    }

    public function testCombinedStatic(UnitTester $I)
    {
        $I->assertEquals(
            ['test_1', 'test_2', 'test_3'],
            Combined3::config()->get('first')
        );

        // Test that unset values are ignored
        $I->assertEquals(
            ['test_1', 'test_3'],
            Combined3::config()->get('second')
        );
    }

    public function testMerges(UnitTester $I)
    {
        $result = Priority::mergeArray(
            ['A' => 1, 'B' => 2, 'C' => 3],
            ['C' => 4, 'D' => 5]
        );
        $I->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 5],
            $result
        );

        $result = Priority::mergeArray(
            ['C' => 4, 'D' => 5],
            ['A' => 1, 'B' => 2, 'C' => 3]
        );
        $I->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => 4, 'D' => 5],
            $result
        );

        $result = Priority::mergeArray(
            [ 'C' => [4, 5, 6], 'D' => 5 ],
            [ 'A' => 1, 'B' => 2, 'C' => [1, 2, 3] ]
        );
        $I->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => [1, 2, 3, 4, 5, 6], 'D' => 5],
            $result
        );

        $result = Priority::mergeArray(
            ['A' => 1, 'B' => 2, 'C' => [1, 2, 3]],
            ['C' => [4, 5, 6], 'D' => 5]
        );
        $I->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => [4, 5, 6, 1, 2, 3], 'D' => 5],
            $result
        );

        $result = Priority::mergeArray(
            ['A' => 1, 'B' => 2, 'C' => ['Foo' => 1, 'Bar' => 2], 'D' => 3],
            ['C' => ['Bar' => 3, 'Baz' => 4]]
        );
        $I->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => ['Foo' => 1, 'Bar' => 2, 'Baz' => 4], 'D' => 3],
            $result
        );

        $result = Priority::mergeArray(
            ['C' => ['Bar' => 3, 'Baz' => 4]],
            ['A' => 1, 'B' => 2, 'C' => ['Foo' => 1, 'Bar' => 2], 'D' => 3]
        );
        $I->assertEquals(
            ['A' => 1, 'B' => 2, 'C' => ['Foo' => 1, 'Bar' => 3, 'Baz' => 4], 'D' => 3],
            $result
        );
    }

    public function testForClass(UnitTester $I)
    {
        $config = DefinesFoo::config();
        // Set values
        $I->assertTrue(isset($config->not_foo));
        $I->assertFalse(empty($config->not_foo));
        $I->assertEquals(1, $config->not_foo);

        // Unset values
        $I->assertFalse(isset($config->bar));
        $I->assertTrue(empty($config->bar));
        $I->assertNull($config->bar);
    }
}
