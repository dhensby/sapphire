<?php
namespace Core\Manifest;
use SilverStripe\Core\Manifest\PrioritySorter;
use \UnitTester;

class PrioritySorterCest
{
    /**
     * @var PrioritySorter
     */
    protected $sorter;

    public function _before(UnitTester $I)
    {
        $modules = [
            'module/one' => 'I am module one',
            'module/two' => 'I am module two',
            'module/three' => 'I am module three',
            'module/four' => 'I am module four',
            'module/five' => 'I am module five',
        ];
        $this->sorter = new PrioritySorter($modules);
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testModuleSortingWithNoVarsAndNoRest(UnitTester $I)
    {
        $this->sorter->setPriorities([
            'module/three',
            'module/one',
            'module/two',
        ]);

        $result = $this->sorter->getSortedList();
        $I->assertEquals([
            'module/three',
            'module/one',
            'module/two',
            'module/four',
            'module/five',
        ], array_keys($result));
    }

    public function testModuleSortingWithVarsAndNoRest(UnitTester $I)
    {
        $this->sorter->setPriorities([
            'module/three',
            '$project',
        ])
            ->setVariable('$project', 'module/one');

        $result = $this->sorter->getSortedList();

        $I->assertEquals([
            'module/three',
            'module/one',
            'module/two',
            'module/four',
            'module/five',
        ], array_keys($result));
    }

    public function testModuleSortingWithNoVarsAndWithRest(UnitTester $I)
    {
        $this->sorter->setPriorities([
            'module/two',
            '$other_modules',
            'module/four',
        ])
            ->setRestKey('$other_modules');
        $result = $this->sorter->getSortedList();
        $I->assertEquals([
            'module/two',
            'module/one',
            'module/three',
            'module/five',
            'module/four',
        ], array_keys($result));
    }

    public function testModuleSortingWithVarsAndWithRest(UnitTester $I)
    {
        $this->sorter->setPriorities([
            'module/two',
            'other_modules',
            '$project',
        ])
            ->setVariable('$project', 'module/four')
            ->setRestKey('other_modules');

        $result = $this->sorter->getSortedList();
        $I->assertEquals([
            'module/two',
            'module/one',
            'module/three',
            'module/five',
            'module/four',
        ], array_keys($result));
    }
}
