<?php
namespace Core\Startup;
use SilverStripe\Core\Tests\Startup\ErrorControlChainTest\ErrorControlChainTest_Chain;
use \UnitTester;

class ErrorControlChainCest
{
    public function _before(UnitTester $I, $scenario)
    {
        // Check we can run PHP at all
        $null = is_writeable('/dev/null') ? '/dev/null' : 'NUL';
        exec("php -v 2> $null", $out, $rv);

        if ($rv != 0) {
            $scenario->skip("Can't run PHP from the command line - is it in your path?");
        }
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testErrorSuppression(UnitTester $I)
    {

        // Errors disabled by default
        $chain = new ErrorControlChainTest_Chain();
        $chain->setDisplayErrors('Off'); // mocks display_errors: Off
        $initialValue = null;
        $whenNotSuppressed = null;
        $whenSuppressed = null;
        $chain->then(function (ErrorControlChainTest_Chain $chain) use (
            &$initialValue,
            &$whenNotSuppressed,
            &$whenSuppressed
        ) {
            $initialValue = $chain->getDisplayErrors();
            $chain->setSuppression(false);
            $whenNotSuppressed = $chain->getDisplayErrors();
            $chain->setSuppression(true);
            $whenSuppressed = $chain->getDisplayErrors();
        })->execute();

        // Disabled errors never un-disable
        $I->assertEquals(0, $initialValue); // Chain starts suppressed
        $I->assertEquals(0, $whenSuppressed); // false value used internally when suppressed
        $I->assertEquals('Off', $whenNotSuppressed); // false value set by php ini when suppression lifted
        $I->assertEquals('Off', $chain->getDisplayErrors()); // Correctly restored after run

        // Errors enabled by default
        $chain = new ErrorControlChainTest_Chain();
        $chain->setDisplayErrors('Yes'); // non-falsey ini value
        $initialValue = null;
        $whenNotSuppressed = null;
        $whenSuppressed = null;
        $chain->then(function (ErrorControlChainTest_Chain $chain) use (
            &$initialValue,
            &$whenNotSuppressed,
            &$whenSuppressed
        ) {
            $initialValue = $chain->getDisplayErrors();
            $chain->setSuppression(true);
            $whenSuppressed = $chain->getDisplayErrors();
            $chain->setSuppression(false);
            $whenNotSuppressed = $chain->getDisplayErrors();
        })->execute();

        // Errors can be suppressed an un-suppressed when initially enabled
        $I->assertEquals(0, $initialValue); // Chain starts suppressed
        $I->assertEquals(0, $whenSuppressed); // false value used internally when suppressed
        $I->assertEquals('Yes', $whenNotSuppressed); // false value set by php ini when suppression lifted
        $I->assertEquals('Yes', $chain->getDisplayErrors()); // Correctly restored after run

        // Fatal error
        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                Foo::bar(); // Non-existant class causes fatal error
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $I->assertEquals('Done', $out);

        // User error

        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                user_error('Error', E_USER_ERROR);
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $I->assertEquals('Done', $out);

        // Recoverable error

        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                $x = function (ErrorControlChain $foo) {
                };
                $x(1); // Calling against type
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $I->assertEquals('Done', $out);

        // Memory exhaustion

        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                ini_set('memory_limit', '10M');
                $a = array();
                while (1) {
                    $a[] = 1;
                }
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $I->assertEquals('Done', $out);

        // Exceptions

        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                throw new Exception("bob");
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $I->assertEquals('Done', $out);
    }

    public function testExceptionSuppression(UnitTester $I)
    {
        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                throw new Exception('This exception should be suppressed');
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $I->assertEquals('Done', $out);
    }

    public function testErrorControl(UnitTester $I)
    {
        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                echo 'preThen,';
            })
            ->thenIfErrored(function () {
                echo 'preThenIfErrored,';
            })
            ->thenAlways(function () {
                echo 'preThenAlways,';
            })
            ->then(function () {
                user_error('An error', E_USER_ERROR);
            })
            ->then(function () {
                echo 'postThen,';
            })
            ->thenIfErrored(function () {
                echo 'postThenIfErrored,';
            })
            ->thenAlways(function () {
                echo 'postThenAlways,';
            })
            ->executeInSubprocess();

        $I->assertEquals(
            "preThen,preThenAlways,postThenIfErrored,postThenAlways,",
            $out
        );
    }

    public function testSuppressionControl(UnitTester $I)
    {
        // Turning off suppression before execution

        $chain = new ErrorControlChainTest_Chain();
        $chain->setSuppression(false);

        list($out, $code) = $chain
            ->then(function ($chain) {
                Foo::bar(); // Non-existant class causes fatal error
            })
            ->executeInSubprocess(true);

        $I->assertContains('Fatal error', $out);
        $I->assertContains('Foo', $out);

        // Turning off suppression during execution

        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function ($chain) {
                $chain->setSuppression(false);
                Foo::bar(); // Non-existent class causes fatal error
            })
            ->executeInSubprocess(true);

        $I->assertContains('Fatal error', $out);
        $I->assertContains('Foo', $out);
    }

    public function testDoesntAffectNonFatalErrors(UnitTester $I)
    {
        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                $array = null;
                if (@$array['key'] !== null) {
                    user_error('Error', E_USER_ERROR);
                }
            })
            ->then(function () {
                echo "Good";
            })
            ->thenIfErrored(function () {
                echo "Bad";
            })
            ->executeInSubprocess();

        $I->assertContains("Good", $out);
    }

    public function testDoesntAffectCaughtExceptions(UnitTester $I)
    {
        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                try {
                    throw new Exception('Error');
                } catch (Exception $e) {
                    echo "Good";
                }
            })
            ->thenIfErrored(function () {
                echo "Bad";
            })
            ->executeInSubprocess();

        $I->assertContains("Good", $out);
    }

    public function testDoesntAffectHandledErrors(UnitTester $I)
    {
        $chain = new ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                set_error_handler(
                    function () {
                        /* NOP */
                    }
                );
                user_error('Error', E_USER_ERROR);
            })
            ->then(function () {
                echo "Good";
            })
            ->thenIfErrored(function () {
                echo "Bad";
            })
            ->executeInSubprocess();

        $I->assertContains("Good", $out);
    }

    public function testMemoryConversion(UnitTester $I)
    {
        $chain = new ErrorControlChainTest_Chain();

        $I->assertEquals(200, $chain->translateMemstring('200'));
        $I->assertEquals(300, $chain->translateMemstring('300'));

        $I->assertEquals(2 * 1024, $chain->translateMemstring('2k'));
        $I->assertEquals(3 * 1024, $chain->translateMemstring('3K'));

        $I->assertEquals(2 * 1024 * 1024, $chain->translateMemstring('2m'));
        $I->assertEquals(3 * 1024 * 1024, $chain->translateMemstring('3M'));

        $I->assertEquals(2 * 1024 * 1024 * 1024, $chain->translateMemstring('2g'));
        $I->assertEquals(3 * 1024 * 1024 * 1024, $chain->translateMemstring('3G'));

        $I->assertEquals(200, $chain->translateMemstring('200foo'));
        $I->assertEquals(300, $chain->translateMemstring('300foo'));
    }
}
