<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;

/**
 * A special type Int field used for primary keys.
 *
 * @todo Allow for custom limiting/filtering of scaffoldFormField dropdown
 */
class DBPrimaryKey extends DBVarchar
{
    /**
     * @var DataObject
     */
    protected $object;

    private static $default_search_filter_class = 'ExactMatchFilter';

    /**
     * @param string $name
     * @param DataObject $object The object that this is primary key for (should have a relation with $name)
     */
    public function __construct($name, $object = null)
    {
        $this->object = $object;
        parent::__construct($name, 36);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return null;
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        parent::setValue($value, $record, $markChanged);

        if ($record instanceof DataObject) {
            $this->object = $record;
        }
    }

//    public function getIndexType()
//    {
//        return DBField::TYPE_UNIQUE;
//    }
}
