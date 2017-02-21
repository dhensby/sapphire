<?php

namespace SilverStripe\ORM\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use InvalidArgumentException;

/**
 * Matches textual content with a LIKE '%keyword%' construct.
 */
class PartialMatchFilter extends SearchFilter
{

    public function getSupportedModifiers()
    {
        return ['not', 'nocase', 'case'];
    }

    /**
     * Apply the match filter to the given variable value
     *
     * @param string $value The raw value
     * @return string
     */
    protected function getMatchPattern($value)
    {
        return "%$value%";
    }

    /**
     * Apply filter criteria to a SQL query.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        if ($this->aggregate) {
            throw new InvalidArgumentException(sprintf(
                'Aggregate functions can only be used with comparison filters. See %s',
                $this->fullName
            ));
        }

        return parent::apply($query);
    }

    protected function applyOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $comparisonClause = $this->generateComparisonClause(
            $this->getDbName(),
            false, // exact?
            false, // negate?
            $this->getCaseSensitive()
        );

        $clause = [$comparisonClause => $this->getMatchPattern($this->getValue())];

        return $this->aggregate ?
            $this->applyAggregate($query, $clause) :
            $query->where($clause);
    }

    protected function applyMany(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $whereClause = array();
        $comparisonClause = $this->generateComparisonClause(
            $this->getDbName(),
            false, // exact?
            false, // negate?
            $this->getCaseSensitive()
        );
        foreach ($this->getValue() as $value) {
            $whereClause[] = array($comparisonClause => $this->getMatchPattern($value));
        }
        return $query->whereAny($whereClause);
    }

    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $comparisonClause = $this->generateComparisonClause(
            $this->getDbName(),
            false, // exact?
            true, // negate?
            $this->getCaseSensitive()
        );
        return $query->where(array(
            $comparisonClause => $this->getMatchPattern($this->getValue())
        ));
    }

    protected function excludeMany(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $values = $this->getValue();
        $comparisonClause = $this->generateComparisonClause(
            $this->getDbName(),
            false, // exact?
            true, // negate?
            $this->getCaseSensitive()
        );
        $parameters = array();
        foreach ($values as $value) {
            $parameters[] = $this->getMatchPattern($value);
        }
        // Since query connective is ambiguous, use AND explicitly here
        $count = count($values);
        $predicate = implode(' AND ', array_fill(0, $count, $comparisonClause));
        return $query->where(array($predicate => $parameters));
    }

    public function isEmpty()
    {
        return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
    }
}
