<?php

namespace SilverStripe\Dev\Tests\SSListExporterTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DummyDataObject extends DataObject implements TestOnly
{
    private static $table_name = 'SSLET_DummyDataObject';
}
