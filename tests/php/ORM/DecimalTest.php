<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;

class DecimalTest extends SapphireTest
{

    protected static $fixture_file = 'DecimalTest.yml';

    protected $testDataObject;

    protected static $extra_dataobjects = array(
        DecimalTest\TestObject::class,
    );

    protected function setUp()
    {
        parent::setUp();
        $this->testDataObject = $this->objFromFixture(DecimalTest\TestObject::class, 'test-dataobject');
    }

    public function testDefaultValue()
    {
        $this->assertEquals(
            0,
            $this->testDataObject->MyDecimal1
        );
    }

    public function testSpecifiedDefaultValue()
    {
        $this->assertEquals(
            2.5,
            $this->testDataObject->MyDecimal2
        );
    }

    public function testInvalidSpecifiedDefaultValue()
    {
        $this->assertEquals(
            0,
            $this->testDataObject->MyDecimal3
        );
    }

    public function testSpecifiedDefaultValueInDefaultsArray()
    {
        $this->assertEquals(
            4,
            $this->testDataObject->MyDecimal4
        );
    }
}
