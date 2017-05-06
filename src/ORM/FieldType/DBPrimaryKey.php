<?php

namespace SilverStripe\ORM\FieldType;

use Doctrine\DBAL\Schema\Column;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\Backtrace;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;

/**
 * A special type Int field used for primary keys.
 *
 * @todo Allow for custom limiting/filtering of scaffoldFormField dropdown
 */
class DBPrimaryKey extends DBInt
{
    /**
     * @var DataObject
     */
    protected $object;

    private static $default_search_filter_class = 'ExactMatchFilter';

    /**
     * @var bool
     */
    protected $autoIncrement = true;

    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    public function getDBOptions()
    {
        $options = parent::getDBOptions() + [
            'autoincrement' => $this->getAutoIncrement(),
            'notnull' => true,
        ];
        unset($options['default']);
        return $options;
    }

    public function augmentDBTable($table)
    {
        parent::augmentDBTable($table);

        $table->setPrimaryKey([
            Convert::symbol2sql($this->getName()),
        ]);
    }

    /**
     * @param string $name
     * @param DataObject $object The object that this is primary key for (should have a relation with $name)
     */
    public function __construct($name, $object = null)
    {
        $this->object = $object;
        parent::__construct($name);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return null;
    }

    public function scaffoldSearchField($title = null)
    {
        parent::scaffoldFormField($title);
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        parent::setValue($value, $record, $markChanged);

        if ($record instanceof DataObject) {
            $this->object = $record;
        }
    }
}
