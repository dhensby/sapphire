<?php

namespace SilverStripe\ORM;

use SilverStripe\ORM\Connect\MySQLQueryBuilder;
use SilverStripe\ORM\Queries\SQLConditionalExpression;
use SilverStripe\ORM\Queries\SQLConditionGroup;
use SilverStripe\ORM\Queries\SQLExpression;
use SilverStripe\ORM\Queries\SQLSelect;

/**
 * Represents a subgroup inside a WHERE clause in a {@link DataQuery}
 *
 * Stores the clauses for the subgroup inside a specific {@link SQLSelect} object.
 * All non-where methods call their DataQuery versions, which uses the base
 * query object.
 */
class DataQuery_SubGroup extends DataQuery implements SQLConditionGroup
{

    /**
     * @var SQLSelect
     */
    protected $whereQuery;

    public function __construct(DataQuery $base, $connective)
    {
        parent::__construct($base->dataClass);
        $this->query = $base->query;
        $this->whereQuery = new SQLSelect();
        $this->whereQuery->setConnective($connective);

        $base->where($this);
    }

    public function where($filter)
    {
        if ($filter) {
            $this->whereQuery->addWhere($filter);
        }

        return $this;
    }

    public function whereAny($filter)
    {
        if ($filter) {
            $this->whereQuery->addWhereAny($filter);
        }

        return $this;
    }

    public function conditionSQL(&$parameters)
    {
        $parameters = array();

        // Ignore empty conditions
        $where = $this->whereQuery->getWhere();
        if (empty($where)) {
            return null;
        }

        $sql = (new MySQLQueryBuilder())->buildWhereFragment($this->whereQuery, $parameters);
//        $conditions = [];
//        $parameters = [];
//        foreach ($where as $clauses) {
//            var_export($clauses);
//            if ($clauses instanceof SQLConditionGroup) {
//
//            }
//            foreach ($clauses as $clause => $params) {
//                $conditions[] = $clause;
////                if ($params instanceof SQLConditionalExpression) {
//////                    var_export($params);
////                    $params->getWhereParameterised($parameters);
////                } else {
////                    var_export([
////                        'clause' => $clause,
////                        'params' => $params,
////                        'conditions' => $conditions,
////                        'parameters' => $parameters
////                    ]);
//                    $parameters = array_merge($parameters, $params);
////                }
//            }
//        }
//
//        $connective = $this->whereQuery->getConnective();
//
//        $sql = " WHERE (" . implode(") {$connective} (", $conditions) . ")";

        return preg_replace('/^\s*WHERE\s*/i', '', $sql);
    }
}
