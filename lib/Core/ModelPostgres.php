<?php
/*
 * (c) 2010 Damien Corpataux
 *
 * LICENSE
 * This library is licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * Model class.
 * Deals with database transactions.
 * @package xFreemwork
**/
abstract class xModelPostgres extends xModel {

    /**
     * @see xModel::get()
     * @return array
     */
    function get() {
        $sql = implode(' ', array(
            $this->sql_select(),
            $this->sql_from(),
            $this->sql_join(),
            $this->sql_where(),
            $this->sql_group(),
            $this->sql_order(),
            $this->sql_limit()
        ));
        return $this->query($sql);
    }

    /**
     * @see xModel::post()
     * @return array
     */
    function post() {
        // Ensures for mandatory fields presence, even if no validation implemented
        if (array_intersect($this->post, array_keys($this->params)) != $this->post)
            throw new Exception('Missing mandatory params for post action: '.implode(',', $this->post), 400);
        // Validates params
        $invalids = array_intersect_key($this->invalids(), $this->params);
        // Ignore invalids for fields that are not mandatory and not given
        if ($this->post)
            foreach($invalids as $key => $value)
                if (!isset($this->params[$key]) && !in_array($key, $this->post))
                    unset($invalids[$key]);
        // Prevents updating an item with invalid data
        if ($invalids) throw new xException("Invalid item data", 400, array(
            'invalids' => $invalids,
            'params' => $this->params
        ));
        // Starts sql generation
        $updates = array();
        foreach ($this->fields_values(true) as $field => $value) {
            $updates[] = "{$field} = ".$this->escape($value, $this->modelfield($field));
        }
        // Creates final sql
        $sql = implode(' ', array(
            "UPDATE \"{$this->maintable}\" SET ",
            implode(', ', $updates),
            $this->sql_where(true)
        ));
        return $this->query($sql);
    }

    /**
     * @see xModel::put()
     * @return array
     */
    function put() {
        // 201 Created or 304 Not Modified or 409 Conflict
        // Ensures for mandatory fields presence, even if no validation implemented
        if (array_intersect($this->put, array_keys($this->params)) != $this->put)
            throw new Exception('Missing mandatory params for put action: '.implode(',', $this->put), 400);
        // Validates params
        $invalids = $this->invalids();
        // Ignore invalids for fields that are not mandatory and not given
        if ($this->put)
            foreach($invalids as $key => $value)
                if (!isset($this->params['$key']) && !in_array($key, $this->put))
                    unset($invalids[$key]);
        // Prevents inserting an invalid item
        if ($invalids) throw new xException("Invalid item data", 400, array(
            'invalids' => $invalids,
            'params' => $this->params
        ));
        // Starts sql generation
        $sqlF = $sqlV = array();
        foreach ($this->fields_values(true) as $field => $value) {
            $sqlF[] = "\"$field\"";
            $sqlV[] = $this->escape($value, $this->modelfield($field));
        }
        // Creates final sql
        $sql = "INSERT INTO \"{$this->maintable}\"".
            " (".implode(', ', $sqlF).") VALUES (".implode(', ', $sqlV).")";
        return $this->query($sql);
    }

    /**
     * @see xModel::delete()
     * @return array
     */
    function delete() {
        // 404 Not Found or 200 OK (default)
        // Ensures mandatory fields presence
        if (array_intersect($this->delete, array_keys($this->params)) != $this->delete)
            throw new Exception('Missing mandatory params for delete action: '.implode(',', $this->delete), 400);
        // Starts sql generation
        $sql = "DELETE FROM {$this->maintable}".$this->sql_where(false, true);
        return $this->query($sql);
    }

    /**
     * @see xModel::count()
     * @return int
     */
    function count() {
        $sql = implode(' ', array(
            "SELECT count(*) as count",
            $this->sql_from(),
            $this->sql_join(),
            $this->sql_where()
        ));
        $count = array_shift($this->query($sql));
        return $count['count'];
    }

    /**
     * @see xModel::escape()
     * @return string
     */
    function escape($value, $field = null) {
        if (is_null($value)) {
            return 'NULL';
        } else if ((!$this->constants || in_array($field, $this->constants)) && in_array($value, $this->sql_constants())) {
            return $value;
        }
        return "'".pg_escape_string($value)."'";
    }
    function sql_constants() {
        // TODO: to be completed with all mysql constants
        return array(
            'CURRENT_TIMESTAMP',
            'NULL',
            'NOT NULL'
        );
    }

    /**
     * @see xModel::sql_select()
     * @return string
     */
    function sql_select() {
        $fragments = xUtil::arrize($this->return);
        // Replaces * with all fields
        foreach ($fragments as $key => $fragment) {
            if ($fragment != '*') continue;
            unset($fragments[$key]);
            foreach ($this->mapping as $model_field => $db_field) {
                $fragments[] = "\"{$this->maintable}\".\"{$db_field}\" AS \"{$model_field}\"";
            }
        }
        // Replaces model fields names with db fields names
        // TODO: the regexp to be able to replace some and somefield without trouble
        /*
        foreach ($this->mapping as $modelfield => $dbfield) {
            $fragments = preg_replace("/($modelfield)/", "{$dbfield}", $fragments);
        }
        */
        // Replaces joined tables db fields name with model fields names
        $joins = xUtil::filter_keys($this->joins, xUtil::arrize(@$this->params['xjoin']));
        foreach ($joins as $model_name => $join) {
            $model = xModel::load($model_name);
            foreach($model->mapping as $model_field => $db_field) {
                $fragments[] = "\"{$model->maintable}\".\"{$db_field}\" AS \"{$model_name}_{$model_field}\"";
            }
        }
        return " SELECT ".implode(', ', $fragments);
    }

    /**
     * @see xModel::sql_from()
     * @return string
     */
    function sql_from() {
        return " FROM \"{$this->maintable}\"";
    }

    /**
     * @see xModel::sql_where()
     * @return string
     */
    function sql_where($primary_only = false) {
        $fields_values = $this->fields_values();
        // Sets WHERE 1=0 if the 1st where clause is OR
        $sql = @$this->params[array_shift(array_keys($fields_values)).'_operator'] == 'OR' ?  ' WHERE 1=0' : ' WHERE 1=1';
        // Adds where clause conditions
        foreach ($fields_values as $field => $value) {
            $modelfield = $this->modelfield($field);
            // If applicable, skips field if not a primary key field
            if ($primary_only && !in_array($modelfield, $this->primary)) continue;
            // Adds the condition operator to the where clause
            if(@$this->params["{$modelfield}_operator"]) {
                $operator = $this->params["{$modelfield}_operator"];
                // Check if operator is allowed
                $allowed_operators = array('AND', 'OR');
                if (!in_array(strtoupper($operator), $allowed_operators))
                    throw new xException("Operator not allowed: {$operator}", 400);
                $sql .= " {$operator} ";
            } else {
                // Default operator
                $sql .= ' AND ';
            }
            // Adds the condition field to the where clause
            $sql .= " {$field}";
            // Adds the condition comparator to the where clause
            if (@$this->params["{$modelfield}_comparator"]) {
                $comparator = $this->params["{$modelfield}_comparator"];
                // Check if comparator is allowed
                $allowed_comparators = array('=', '!=', '<', '>', '<=', '>=', 'LIKE', 'IS', 'IS NOT');
                if (!in_array(strtoupper($comparator), $allowed_comparators))
                    throw new xException("Comparator not allowed: {$comparator}", 400);
                $sql .= " {$comparator} ";
            } elseif (is_array($value)) {
                $sql .= ' IN ';
            } elseif (is_null($value)) {
                $sql .= ' IS ';
            } else {
                // Default comparator
                $sql .= ' = ';
            }
            // Adds the condition value to the where clause
            if (is_array($value)) {
                $values = array();
                foreach ($value as $val) $values[] = $this->escape($val, $this->modelfield($field));
                $sql .= ' ('.implode(',', $values).')';
            } else {
                // Adds condition value to the where clause
                $sql .= $this->escape($value, $this->modelfield($field));
            }
        }
        return $sql;
    }

    /**
     * @see xModel::sql_join()
     * @return string
     */
    function sql_join() {
        $joins = xUtil::filter_keys($this->joins, xUtil::arrize(@$this->params['xjoin']));
        return implode($joins, ' ');
    }

    /**
     * @see xModel::sql_order()
     * @return string
     */
    function sql_order() {
        $sql = '';
        if ($this->order && $this->order_by) {
            $fields = array();
            foreach(xUtil::arrize($this->order_by) as $field) $fields[] = $this->dbfield($field);
            $sql = ' ORDER BY '.implode(',', $fields)." {$this->order}";
        }
        return $sql;
    }

    /**
     * @see xModel::sql_group()
     * @return string
     */
    function sql_group() {
        $sql = '';
        if ($this->group_by) {
            $fields = array();
            foreach(xUtil::arrize($this->group_by) as $field) $fields[] = $this->dbfield($field);
            $sql = ' GROUP BY '.implode(',', $fields);
        }
        return $sql;
    }

    /**
     * @see xModel::sql_limit()
     * @return string
     */
    function sql_limit() {
        $sql = '';
        if (@$this->params['xlimit']) $sql .= " LIMIT {$this->params['xlimit']}";
        if (@$this->params['xoffset']) $sql .= " OFFSET {$this->params['xoffset']}";
        return $sql;
    }

    /**
     * @see xModel::query()
     * @return array
     */
    function query($sql) {
        $db = xContext::$db;
        // Executes query
        xContext::$log->log("Executing query: \n{$sql}", $this);
        $qr = pg_query($db, $sql);
        if (!$qr) throw new xException("Invalid query: $sql # " . pg_last_error($db));
        // Creates an array of results
        if (is_resource($qr)) {
            // Returns an empty array if no row was retrieved
            if (pg_num_rows($qr) < 1) return array();
            // Translates db fields into model fields,
            // keeping the result field name if not found in mapping
            $fields = array();
            foreach(array_keys(pg_fetch_assoc($qr)) as $dbfield) {
                $fields[] = $this->modelfield($dbfield);
            }
            pg_result_seek($qr, 0);
            // Creates the result array
            $result = array();
            while ($row = pg_fetch_assoc($qr)) {
                $result[] = array_combine($fields, $row);;
            }
        } else {
            $result = array(
                'insertid' => false, //TODO: mysql_insert_id($db),
                'affectedrows' => pg_affected_rows($db),
                'info' => array(), //TODO: mysql_info($db),
                'raw' => $qr
            );
        }
        if (is_resource($qr)) pg_free_result($qr);
        return $result;
    }
}

?>
