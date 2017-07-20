<?php
namespace Core;
use SilverStripe\Core\Environment;
use \UnitTester;

class MemoryLimitCest
{
    protected $origMemLimitMax;
    protected $origTimeLimitMax;
    protected $origMemLimit;
    protected $origTimeLimit;

    public function _before(UnitTester $I, $scenario)
    {
        // see http://www.hardened-php.net/suhosin/configuration.html#suhosin.memory_limit
        if (in_array('suhosin', get_loaded_extensions())) {
            $scenario->skip("This test cannot be run with suhosin installed");
        } else {
            $this->origMemLimit = ini_get('memory_limit');
            $this->origTimeLimit = ini_get('max_execution_time');
            $this->origMemLimitMax = Environment::getMemoryLimitMax();
            $this->origTimeLimitMax = Environment::getTimeLimitMax();
            Environment::setMemoryLimitMax(null);
            Environment::setTimeLimitMax(null);
        }
    }

    public function _after(UnitTester $I)
    {
        if (!in_array('suhosin', get_loaded_extensions())) {
            ini_set('memory_limit', $this->origMemLimit);
            set_time_limit($this->origTimeLimit);
            Environment::setMemoryLimitMax($this->origMemLimitMax);
            Environment::setTimeLimitMax($this->origTimeLimitMax);
        }
    }

    // tests
    public function testIncreaseMemoryLimitTo(UnitTester $I)
    {
        ini_set('memory_limit', '64M');
        Environment::setMemoryLimitMax('256M');

        // It can go up
        Environment::increaseMemoryLimitTo('128M');
        $I->assertEquals('128M', ini_get('memory_limit'));

        // But not down
        Environment::increaseMemoryLimitTo('64M');
        $I->assertEquals('128M', ini_get('memory_limit'));

        // Test the different kinds of syntaxes
        Environment::increaseMemoryLimitTo(1024*1024*200);
        $I->assertEquals('200M', ini_get('memory_limit'));

        Environment::increaseMemoryLimitTo('109600K');
        $I->assertEquals('200M', ini_get('memory_limit'));

        // Attempting to increase past max size only sets to max
        Environment::increaseMemoryLimitTo('1G');
        $I->assertEquals('256M', ini_get('memory_limit'));

        // No argument means unlimited (but only if originally allowed)
        if (is_numeric($this->origMemLimitMax) && $this->origMemLimitMax < 0) {
            Environment::increaseMemoryLimitTo();
            $I->assertEquals(-1, ini_get('memory_limit'));
        }
    }

    public function testIncreaseTimeLimitTo(UnitTester $I, $scenario)
    {
        // Can't change time limit
        if (!set_time_limit(6000)) {
            $scenario->skip("Cannot change time limit");
        }

        // It can go up
        $I->assertTrue(Environment::increaseTimeLimitTo(7000));
        $I->assertEquals(7000, ini_get('max_execution_time'));

        // But not down
        $I->assertTrue(Environment::increaseTimeLimitTo(5000));
        $I->assertEquals(7000, ini_get('max_execution_time'));

        // 0/nothing means infinity
        $I->assertTrue(Environment::increaseTimeLimitTo());
        $I->assertEquals(0, ini_get('max_execution_time'));

        // Can't go down from there
        $I->assertTrue(Environment::increaseTimeLimitTo(10000));
        $I->assertEquals(0, ini_get('max_execution_time'));
    }
}
