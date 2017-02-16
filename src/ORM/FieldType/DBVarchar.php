<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\NullableField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\DB;

/**
 * Class Varchar represents a variable-length string of up to 255 characters, designed to store raw text
 *
 * @see DBHTMLText
 * @see DBHTMLVarchar
 * @see DBText
 */
class DBVarchar extends DBString
{

    private static $casting = array(
        "Initial" => "Text",
        "URL" => "Text",
    );

    /**
     * Return the first letter of the string followed by a .
     *
     * @return string
     */
    public function Initial()
    {
        if ($this->exists()) {
            $value = $this->RAW();
            return $value[0] . '.';
        }
        return null;
    }

    /**
     * Ensure that the given value is an absolute URL.
     *
     * @return string
     */
    public function URL()
    {
        $value = $this->RAW();
        if (preg_match('#^[a-zA-Z]+://#', $value)) {
            return $value;
        } else {
            return "http://" . $value;
        }
    }

    /**
     * Return the value of the field in rich text format
     * @return string
     */
    public function RTF()
    {
        return str_replace("\n", '\par ', $this->RAW());
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        // Set field with appropriate size
        $field = TextField::create($this->name, $title);
        $field->setMaxLength($this->getSize());

        // Allow the user to select if it's null instead of automatically assuming empty string is
        if (!$this->getNullifyEmpty()) {
            return NullableField::create($field);
        }
        return $field;
    }
}
