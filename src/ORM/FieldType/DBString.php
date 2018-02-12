<?php

namespace SilverStripe\ORM\FieldType;

use Doctrine\DBAL\Types\Type;

/**
 * An abstract base class for the string field types (i.e. Varchar and Text)
 */
abstract class DBString extends DBField
{

    protected $size;

    /**
     * @var array
     */
    private static $casting = array(
        "LimitCharacters" => "Text",
        "LimitCharactersToClosestWord" => "Text",
        "LimitWordCount" => "Text",
        "LowerCase" => "Text",
        "UpperCase" => "Text",
        "Plain" => "Text",
    );

    private static $default_size = 255;

    /**
     * Set the default value for "nullify empty"
     *
     * @param string $name The name of the field
     * @param int $size The maximum size of the field, in terms of characters
     * @param array $options Optional parameters, e.g. array("nullifyEmpty"=>false).
     *                       See {@link StringField::setOptions()} for information on the available options
     */
    public function __construct($name = null, $size = null, $options = [])
    {
        $this->options['nullifyEmpty'] = true;

        $size = $size ?: $this->config()->default_size;

        if ($size) {
            $this->setSize($size);
        }

        parent::__construct($name, $options);
    }

    /**
     * Allow the ability to access the size of the field programatically. This
     * can be useful if you want to have text fields with a length limit that
     * is dictated by the DB field.
     *
     * TextField::create('Title')->setMaxLength(singleton('SiteTree')->dbObject('Title')->getSize())
     *
     * @return int The size of the field
     */
    public function getSize()
    {
        return $this->size;
    }

    public function setSize($size)
    {
        if (is_numeric($size) && $size > 0) {
            $this->size = (int)$size;
        }

        return $this;
    }

    public function getDBType()
    {
        return Type::STRING;
    }

    public function getDBOptions()
    {
        return parent::getDBOptions() + [
            'length' => $this->getSize(),
        ];
    }

    /**
     * Update the optional parameters for this field.
     *
     * @param array $options Array of options
     * The options allowed are:
     *   <ul><li>"nullifyEmpty"
     *       This is a boolean flag.
     *       True (the default) means that empty strings are automatically converted to nulls to be stored in
     *       the database. Set it to false to ensure that nulls and empty strings are kept intact in the database.
     *   </li></ul>
     * @return $this
     */
    public function setOptions(array $options = [])
    {
        parent::setOptions($options);

        if (array_key_exists('nullifyEmpty', $options)) {
            $this->options['nullifyEmpty'] = (bool) $options['nullifyEmpty'];
        }
        if (array_key_exists('default', $options)) {
            $this->setDefaultValue($options['default']);
        }
        if (array_key_exists("size", $options)) {
            $this->setSize($options["size"]);
        }
        return $this;
    }

    /**
     * Set whether this field stores empty strings rather than converting
     * them to null.
     *
     * @param $value boolean True if empty strings are to be converted to null
     * @return $this
     */
    public function setNullifyEmpty($value)
    {
        $this->options['nullifyEmpty'] = (bool) $value;
        return $this;
    }

    /**
     * Get whether this field stores empty strings rather than converting
     * them to null
     *
     * @return boolean True if empty strings are to be converted to null
     */
    public function getNullifyEmpty()
    {
        return $this->options['nullifyEmpty'];
    }

    /**
     * (non-PHPdoc)
     * @see core/model/fieldtypes/DBField#exists()
     */
    public function exists()
    {
        $value = $this->RAW();
        // All truthy values and non-empty strings exist ('0' but not (int)0)
        return $value || (is_string($value) && strlen($value));
    }

    public function prepValueForDB($value)
    {
        // Cast non-empty value
        if (strlen($value)) {
            return (string)$value;
        }

        // Return "empty" value
        if ($this->options['nullifyEmpty'] || $value === null) {
            return null;
        }
        return '';
    }

    /**
     * @return string
     */
    public function forTemplate()
    {
        return nl2br(parent::forTemplate());
    }

    /**
     * Limit this field's content by a number of characters.
     * This makes use of strip_tags() to avoid malforming the
     * HTML tags in the string of text.
     *
     * @param int $limit Number of characters to limit by
     * @param string $add Ellipsis to add to the end of truncated string
     * @return string
     */
    public function LimitCharacters($limit = 20, $add = '...')
    {
        $value = $this->Plain();
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        return mb_substr($value, 0, $limit) . $add;
    }

    /**
     * Limit this field's content by a number of characters and truncate
     * the field to the closest complete word. All HTML tags are stripped
     * from the field.
     *
     * @param int $limit Number of characters to limit by
     * @param string $add Ellipsis to add to the end of truncated string
     * @return string Plain text value with limited characters
     */
    public function LimitCharactersToClosestWord($limit = 20, $add = '...')
    {
        // Safely convert to plain text
        $value = $this->Plain();

        // Determine if value exceeds limit before limiting characters
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        // Limit to character limit
        $value = mb_substr($value, 0, $limit);

        // If value exceeds limit, strip punctuation off the end to the last space and apply ellipsis
        $value = preg_replace(
            '/[^\w_]+$/',
            '',
            mb_substr($value, 0, mb_strrpos($value, " "))
        ) . $add;
        return $value;
    }

    /**
     * Limit this field's content by a number of words.
     *
     * @param int $numWords Number of words to limit by.
     * @param string $add Ellipsis to add to the end of truncated string.
     *
     * @return string
     */
    public function LimitWordCount($numWords = 26, $add = '...')
    {
        $value = $this->Plain();
        $words = explode(' ', $value);
        if (count($words) <= $numWords) {
            return $value;
        }

        // Limit
        $words = array_slice($words, 0, $numWords);
        return implode(' ', $words) . $add;
    }

    /**
     * Converts the current value for this StringField to lowercase.
     *
     * @return string Text with lowercase (HTML for some subclasses)
     */
    public function LowerCase()
    {
        return mb_strtolower($this->RAW());
    }

    /**
     * Converts the current value for this StringField to uppercase.
     *
     * @return string Text with uppercase (HTML for some subclasses)
     */
    public function UpperCase()
    {
        return mb_strtoupper($this->RAW());
    }

    /**
     * Plain text version of this string
     *
     * @return string Plain text
     */
    public function Plain()
    {
        return trim($this->RAW());
    }
}
