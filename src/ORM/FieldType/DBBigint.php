<?php

namespace SilverStripe\ORM\FieldType;

use Doctrine\DBAL\Types\Type;
use SilverStripe\ORM\DB;

/**
 * Represents a signed 8 byte integer field. Do note PHP running as 32-bit might not work with Bigint properly, as it
 * would convert the value to a float when queried from the database since the value is a 64-bit one.
 *
 * @package framework
 * @subpackage model
 * @see Int
 */
class DBBigInt extends DBInt
{

    public function getDBType()
    {
        return Type::BIGINT;
    }

    public function getDBOptions()
    {
        return parent::getDBOptions() + [
                'precision' => 8,
                'notnull' => true,
        ];
    }
}
