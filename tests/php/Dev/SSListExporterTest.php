<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\SSListExporter;
use SilverStripe\Dev\Tests\SSListExporterTest\DummyDataObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;
use SilverStripe\View\ArrayData;

class SSListExporterTest extends SapphireTest
{

    protected static $extra_dataobjects = [
        DummyDataObject::class,
    ];

    /**
     * @var SSListExporter
     */
    private $exporter;

    public function setUp()
    {
        parent::setUp();
        $this->exporter = new SSListExporter();
    }

    public function provideClassesForExport()
    {
        return [
            [ArrayList::class, false],
            [DummyDataObject::class, false],
            [DataList::class, Member::class],
            [ArrayData::class, false]
        ];
    }

    /**
     * @dataProvider provideClassesForExport()
     * @param $className
     * @param $constructorParam
     */
    public function testExportStartsWithClassName($className, $constructorParam)
    {
        $obj = $constructorParam
            ? $className::create($constructorParam)
            : $className::create();

        $export = ltrim($this->exporter->export($obj));

        $this->assertStringStartsWith(get_class($obj), $export, 'Export should start with object\'s class name');
    }


    /**
     * @testdox toMap() returns DataObjects's data
     */
    public function testToMapReturnsDataOfDataObjects()
    {
        $data = [
            'Foo' => 'Bar',
            'Baz' => 'Boom',
            'One' => 'Two'
        ];

        $map = $this->exporter->toMap(DummyDataObject::create($data));
        unset($map['ID']);

        $this->assertEquals($data, $map, 'Map should match data passed to DataObject');
    }

    /**
     * @testdox toMap() returns ArrayData's data
     */
    public function testToMapReturnsDataOfArrayData()
    {
        $data = [
            'Foo' => 'Bar',
            'Baz' => 'Boom',
            'One' => 'Two'
        ];

        $map = $this->exporter->toMap(ArrayData::create($data));

        $this->assertEquals($data, $map, 'Map should match data passed to ArrayData');
    }
}
