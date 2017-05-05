<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionCheckboxSetField;
use SilverStripe\Dev\SapphireTest;

class PermissionCheckboxSetFieldTest extends SapphireTest
{
    protected static $fixture_file = 'PermissionCheckboxSetFieldTest.yml';

    public function testHiddenPermissions()
    {
        $f = new PermissionCheckboxSetField(
            'Permissions',
            'Permissions',
            Permission::class,
            'GroupID'
        );
        $f->setHiddenPermissions(
            array('NON-ADMIN')
        );
        $this->assertEquals(
            $f->getHiddenPermissions(),
            array('NON-ADMIN')
        );
        $this->assertContains('ADMIN', $f->Field());
        $this->assertNotContains('NON-ADMIN', $f->Field());
    }

    public function testSaveInto()
    {
        /**
 * @var Group $group
*/
        $group = $this->objFromFixture(Group::class, 'group');  // tested group
        /**
 * @var Group $untouchable
*/
        $untouchable = $this->objFromFixture(Group::class, 'untouchable');  // group that should not change

        $field = new PermissionCheckboxSetField(
            'Permissions',
            'Permissions',
            Permission::class,
            'GroupID',
            $group
        );

        // get the number of permissions before we start
        $baseCount = DataObject::get(Permission::class)->count();

        // there are currently no permissions, save empty checkbox
        $field->saveInto($group);
        $group->flushCache();
        $untouchable->flushCache();
        $this->assertCount(0, $group->Permissions(), 'The tested group has no permissions');

        $this->assertCount(1, $untouchable->Permissions(), 'The other group has one permission');
        $this->assertCount(
            1,
            $untouchable->Permissions()->where(sprintf('%s = %s', Convert::symbol2sql('Code'), Convert::raw2sql('ADMIN'))),
            'The other group has ADMIN permission'
        );

        $this->assertCount($baseCount, DataObject::get(Permission::class), 'There are no orphaned permissions');

        // add some permissions
        $field->setValue(
            array(
                'ADMIN'=>true,
                'NON-ADMIN'=>true
            )
        );

        $field->saveInto($group);
        $group->flushCache();
        $untouchable->flushCache();
        $this->assertCount(
            2,
            $group->Permissions(),
            'The tested group has two permissions permission'
        );
        $this->assertEquals(
            1,
            $group->Permissions()->where(sprintf('%s = %s', Convert::symbol2sql('Code'), Convert::raw2sql('ADMIN')))->count(),
            'The tested group has ADMIN permission'
        );
        $this->assertEquals(
            1,
            $group->Permissions()->where(sprintf('%s = %s', Convert::symbol2sql('Code'), Convert::raw2sql('NON-ADMIN')))->count(),
            'The tested group has CMS_ACCESS_AssetAdmin permission'
        );

        $this->assertEquals(
            1,
            $untouchable->Permissions()->count(),
            'The other group has one permission'
        );
        $this->assertEquals(
            1,
            $untouchable->Permissions()->where(sprintf('%s = %s', Convert::symbol2sql('Code'), Convert::raw2sql('ADMIN')))->count(),
            'The other group has ADMIN permission'
        );

        $this->assertEquals(
            $baseCount+2,
            DataObject::get(Permission::class)->count(),
            'There are no orphaned permissions'
        );

        // remove permission
        $field->setValue(
            array(
            'ADMIN'=>true,
            )
        );

        $field->saveInto($group);
        $group->flushCache();
        $untouchable->flushCache();
        $this->assertEquals(
            1,
            $group->Permissions()->count(),
            'The tested group has 1 permission'
        );
        $this->assertEquals(
            1,
            $group->Permissions()->where(sprintf('%s = %s', Convert::symbol2sql('Code'), Convert::raw2sql('ADMIN')))->count(),
            'The tested group has ADMIN permission'
        );

        $this->assertEquals(
            1,
            $untouchable->Permissions()->count(),
            'The other group has one permission'
        );
        $this->assertEquals(
            1,
            $untouchable->Permissions()->where(sprintf('%s = %s', Convert::symbol2sql('Code'), Convert::raw2sql('ADMIN')))->count(),
            'The other group has ADMIN permission'
        );

        $this->assertEquals(
            $baseCount+1,
            DataObject::get(Permission::class)->count(),
            'There are no orphaned permissions'
        );
    }
}
