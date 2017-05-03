<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\DB;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\NullableField;

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
        if (!$this->getNullifyEmpty()) {
            // Allow the user to select if it's null instead of automatically assuming empty string is
            return new NullableField(new TextField($this->name, $title));
        } else {
            // Automatically determine null (empty string)
            return parent::scaffoldFormField($title);
        }
    }
}
