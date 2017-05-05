<?php

namespace SilverStripe\ORM\Tests;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\PDOConnection;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\MSSQL\MSSQLDatabase;
use SilverStripe\Dev\SapphireTest;
use Exception;
use SilverStripe\ORM\Tests\DatabaseTest\MyObject;

/**
 * @skipUpgrade
*/
class DatabaseTest extends SapphireTest
{

    protected static $extra_dataobjects = array(
        MyObject::class,
    );

    protected $usesDatabase = true;

    public function testDontRequireField()
    {
        // @todo - we don't use this method to change columns any more, they just get dropped
        $this->markTestSkipped('Renaming columns not currently supported');
        $schema = DB::get_schema();
        $this->assertArrayHasKey(
            'MyField',
            $schema->listTableColumns('DatabaseTest_MyObject')
        );

        $schema->dontRequireField('DatabaseTest_MyObject', 'MyField');

        $this->assertArrayHasKey(
            '_obsolete_MyField',
            $schema->listTableColumns('DatabaseTest_MyObject'),
            'Field is renamed to _obsolete_<fieldname> through dontRequireField()'
        );

        static::resetDBSchema(true);
    }

    public function testRenameField()
    {
        $schema = DB::get_schema();

        // @todo - doctrine/dbal doesn't appear to support table renaming
        $this->markTestSkipped('cant rename columns at the moment');
        $schema->renameField('DatabaseTest_MyObject', 'MyField', 'MyRenamedField');

        $this->assertArrayHasKey(
            'MyRenamedField',
            $schema->listTableColumns('DatabaseTest_MyObject'),
            'New fieldname is set through renameField()'
        );
        $this->assertArrayNotHasKey(
            'MyField',
            $schema->listTableColumns('DatabaseTest_MyObject'),
            'Old fieldname isnt preserved through renameField()'
        );

        static::resetDBSchema(true);
    }

    public function testMySQLCreateTableOptions()
    {
        if (!(DB::get_conn()->getDriver() instanceof AbstractMySQLDriver)) {
            $this->markTestSkipped('MySQL only');
        }


        $ret = DB::query(
            sprintf(
                'SHOW TABLE STATUS WHERE %s = %s',
                Convert::symbol2sql('Name'),
                Convert::raw2sql('DatabaseTest_MyObject')
            )
        )->fetch(PDOConnection::FETCH_ASSOC);
        $this->assertEquals(
            $ret['Engine'],
            'InnoDB',
            "MySQLDatabase tables can be changed to InnoDB through DataObject::\$create_table_options"
        );
    }

    public function testTransactions()
    {
        $conn = DB::get_conn();
        if (!$conn->getDatabasePlatform()->supportsTransactions()) {
            $this->markTestSkipped("DB Doesn't support transactions");
            return;
        }

        // Test that successful transactions are comitted
        $obj = new DatabaseTest\MyObject();
        try {
            $conn->transactional(
                function () use ($obj) {
                    $obj->MyField = 'Save 1';
                    $obj->write();
                }
            );
        } catch (Exception $e) {
        }
        $this->assertEquals('Save 1', DatabaseTest\MyObject::get()->first()->MyField);

        // Test failed transactions are rolled back
        $ex = null;
        try {
            $conn->transactional(
                function () use (&$obj) {
                    $obj->MyField = 'Save 2';
                    $obj->write();
                    throw new Exception("error");
                }
            );
        } catch (Exception $ex) {
        }
        $this->assertEquals('Save 1', DatabaseTest\MyObject::get()->first()->MyField);
        $this->assertInstanceOf('Exception', $ex);
        $this->assertEquals('error', $ex->getMessage());
    }
}
