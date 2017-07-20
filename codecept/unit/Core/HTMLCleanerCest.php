<?php
namespace Core;
use Helper\Scenario;
use SilverStripe\View\Parsers\HTMLCleaner;
use \UnitTester;

class HTMLCleanerCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testHTMLClean(UnitTester $I, $scenario)
    {
        $cleaner = HTMLCleaner::inst();

        if ($cleaner) {
            $I->assertEquals(
                $cleaner->cleanHTML('<p>wrong <b>nesting</i></p>'),
                '<p>wrong <b>nesting</b></p>',
                "HTML cleaned properly"
            );
            $I->assertEquals(
                $cleaner->cleanHTML('<p>unclosed paragraph'),
                '<p>unclosed paragraph</p>',
                "HTML cleaned properly"
            );
        } else {
            $scenario->skip('No HTMLCleaner library available (tidy or HTMLBeautifier)');
        }
    }
}
