<?php

namespace SilverStripe\ORM;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use InvalidArgumentException;

/**
 * Represents a has_many list linked against a polymorphic relationship
 */
class PolymorphicHasManyList extends HasManyList
{

    /**
     * Name of foreign key field that references the class name of the relation
     *
     * @var string
     */
    protected $classForeignKey;

    /**
     * Retrieve the name of the class this relation is filtered by
     *
     * @return string
     */
    public function getForeignClass()
    {
        return $this->dataQuery->getQueryParam('Foreign.Class');
    }

    /**
     * Create a new PolymorphicHasManyList relation list.
     *
     * @param string $dataClass The class of the DataObjects that this will list.
     * @param string $foreignField The name of the composite foreign relation field. Used
     * to generate the ID and Class foreign keys.
     * @param string $foreignClass Name of the class filter this relation is filtered against
     */
    public function __construct($dataClass, $foreignField, $foreignClass)
    {
        // Set both id foreign key (as in HasManyList) and the class foreign key
        parent::__construct($dataClass, "{$foreignField}ID");
        $this->classForeignKey = "{$foreignField}Class";

        // Ensure underlying DataQuery globally references the class filter
        $this->dataQuery->setQueryParam('Foreign.Class', $foreignClass);

        // For queries with multiple foreign IDs (such as that generated by
        // DataList::relation) the filter must be generalised to filter by subclasses
        $classNames = Convert::raw2sql(ClassInfo::subclassesFor($foreignClass));
        $this->dataQuery->where(sprintf(
            "\"{$this->classForeignKey}\" IN ('%s')",
            implode("', '", $classNames)
        ));
    }

    /**
     * Adds the item to this relation.
     *
     * It does so by setting the relationFilters.
     *
     * @param DataObject|string $item The DataObject to be added, or its ID
     */
    public function add($item)
    {
        if (is_string($item)) {
            $item = DataObject::get_by_id($this->dataClass, $item);
        } elseif (!($item instanceof $this->dataClass)) {
            user_error(
                "PolymorphicHasManyList::add() expecting a $this->dataClass object, or ID value",
                E_USER_ERROR
            );
        }

        $foreignID = $this->getForeignID();

        // Validate foreignID
        if (!$foreignID) {
            user_error(
                "PolymorphicHasManyList::add() can't be called until a foreign ID is set",
                E_USER_WARNING
            );
            return;
        }
        if (is_array($foreignID)) {
            user_error(
                "PolymorphicHasManyList::add() can't be called on a list linked to mulitple foreign IDs",
                E_USER_WARNING
            );
            return;
        }

        $foreignKey = $this->foreignKey;
        $classForeignKey = $this->classForeignKey;
        $item->$foreignKey = $foreignID;
        $item->$classForeignKey = $this->getForeignClass();

        $item->write();
    }

    /**
     * Remove an item from this relation.
     * Doesn't actually remove the item, it just clears the foreign key value.
     *
     * @param DataObject $item The DataObject to be removed
     * @todo Maybe we should delete the object instead?
     */
    public function remove($item)
    {
        if (!($item instanceof $this->dataClass)) {
            throw new InvalidArgumentException(
                "HasManyList::remove() expecting a $this->dataClass object, or ID",
                E_USER_ERROR
            );
        }

        // Don't remove item with unrelated class key
        $foreignClass = $this->getForeignClass();
        $classNames = ClassInfo::subclassesFor($foreignClass);
        $classForeignKey = $this->classForeignKey;
        $classValueLower = strtolower($item->$classForeignKey);
        if (!array_key_exists($classValueLower, $classNames)) {
            return;
        }

        // Don't remove item which doesn't belong to this list
        $foreignID = $this->getForeignID();
        $foreignKey = $this->foreignKey;

        if (empty($foreignID)
            || (is_array($foreignID) && in_array($item->$foreignKey, $foreignID))
            || $foreignID == $item->$foreignKey
        ) {
            $item->$foreignKey = null;
            $item->$classForeignKey = null;
            $item->write();
        }
    }
}
