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
abstract class xModel extends xController {

    var $table = 'sometablename';

    /**
     * This property is set in the constructor.
     *
     * Name of the main table:
     * i.e. the table affected by the default put(),post(),delete() methods (if not overriden).
     * @var string
     */
    var $maintable = null;

    /**
     * Params to fields mapping.
     * This mapping purpose is to abstract table fields names.
     * @var array
     */
    var $mapping = array(
        'id' => 'id',
        'name' => 'name',
        'example_name' => 'another_name_in_table',
        'shortname' => 'name_in_table'
    );

    /**
     * The primary key field names (model field names).
     * @var array
     */
    var $primary = array();

    /**
     * Fields (model) that accept sql constants (e.g. CURRENT_TIMESTAMP).
     * These fields value, when provided with a mysql constant or function,
     * will not be escaped and enquoted.
     * If empty, any field will accept sql constants.
     * Example array:
     * <code>
     * array('mapping_name')
     * </code>
     * @see xModel::fields_values()
     * @var array
     */
    var $constants = array();

    // Mandatory params for get operations (model fields names)
    var $get = array();

    // Mandatory params for post operations (model fields names)
    var $post = array();

    // Mandatory params for put operations (model fields names)
    var $put = array();

    // Mandatory params for delete operations (model fields names)
    var $delete = array();

    /**
     * Fields to return (model fields names).
     * @see Model::sql_select()
     * @var string|array
     */
    var $return = array('*');

    /**
     * Result sorting fields (model fields names).
     * @see xModel::sql_order()
     * @var string|array
     */
    var $order_by = null; //'id';

    /**
     * Result sorting order.
     * Accepted values: 'ASC' or 'DESC'.
     * @see xModel::sql_order()
     * @var string
     */
    var $order = null;
    
    /**
     * Result group by.
     * Contains model fields name(s).
     * @see xModel::sql_group()
     * @var string|array
     */
    var $group_by = null;
  
    /**
     * SQL joins.
     * Array example:
     * <code>
     * array(
     *    join_name => 'LEFT JOIN foreign_table_name ON this_table_id = foreign_table_id'
     * )
     * </code>
     * @see xModel::sql_join()
     * @var array
     */
    var $joins = array();

    /**
     * Specifies which of the fields accept HTML.
     * Example:
     * <code>
     * array('field-1', 'field-2')
     * </code>
     * @var array
     */
    var $allow_html = array();

    /**
     * Model classes should only be instanciated through the Model::load() method.
     * @see Model::load()
     */
    protected function __construct($params = null) {
        parent::__construct($params);
        $this->maintable = trim(array_shift(explode(',', $this->table)));
        // Strip HTML tags from fields values
        foreach (array_intersect_key($this->params, $this->mapping) as $field => $value) {
            if (in_array($field, $this->allow_html)) continue;
            // Strips HTML tags in params values, dig down into array if necessary
            if (!is_array($this->params[$field])) {
                $this->params[$field] = xUtil::strip_tags($this->params[$field]);
            } else {
                foreach ($this->params[$field] as $key => $value) {
                    $this->params[$field][$key] = xUtil::strip_tags($this->params[$field][$key]);
                }
            }
        }
        // Override model properties from x-params
        if (isset($this->params['xorder'])) $this->order = $this->params['xorder'];
        if (isset($this->params['xorder_by'])) $this->order_by = $this->params['xorder_by'];
        if (isset($this->params['xgroup_by'])) $this->group_by = $this->params['xgroup_by'];
        if (isset($this->params['xreturn'])) $this->return = $this->params['xreturn'];
    }

    /**
     * Loads and returns the model specified object.
     * @param string The model to load.
     *        e.g. item will load the models/item.php file
     *        and return an instance of the ItemModel class.
     * @return xModel
     */
    static function load($name, $params = null, $options = array()) {
        $file = xContext::$basepath."/models/{$name}.php";
        xContext::$log->log("Loading model: $file", 'xModel');
        if (!file_exists($file)) throw new xException("Model file not found (model {$name})", 404);
        require_once($file);
        $class_name = str_replace(array('_', '.'), '', $name)."Model";
        xContext::$log->log("Instanciating model: $class_name", 'xModel');
        $instance = new $class_name($params);
        // Applies options
        foreach ($options as $key => $value) {
            $instance->$key = $value;
        }
        return $instance;
    }

    /**
     * Checks given params values and returns an array containing
     * the invalid params (fields) as key, and true as value.
     * @return array
     */
    function invalids() {
        return array();
    }

    /**
     * Implements the REST get method to access data.
     * Issues a SELECT and returns the result
     * as an associative array.
     * @see xModel::query()
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
     * Implements the REST post method to access data.
     * Issues a UPDATE and returns the result
     * as an associative array.
     * @see xModel::query()
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
            "UPDATE `{$this->maintable}` SET ",
            implode(', ', $updates),
            $this->sql_where(true)
        ));
        return $this->query($sql);
    }

    /**
     * Implements the REST put method to access data.
     * Issues a INSERT and returns the result
     * as an associative array.
     * @see xModel::query()
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
            $sqlF[] = $field;
            $sqlV[] = $this->escape($value, $this->modelfield($field));
        }
        // Creates final sql
        $sql = "INSERT INTO `{$this->maintable}`".
            " (".implode(', ', $sqlF).") VALUES (".implode(', ', $sqlV).")";
        return $this->query($sql);
    }

    /**
     * Implements the REST delete method to access data.
     * Issues a DELETE and returns the result
     * as an associative array.
     * @see xModel::query()
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
     * Issues a COUNT and returns the result
     * @see xModel::query()
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
     * Returns the model field name from the given database field name.
     * @param string The model field name.
     * @return string The databse field name.
     */
    function dbfield($modelfield) {
        $dbfield = @$this->mapping[$modelfield];
        return $dbfield ? $dbfield : $modelfield;
    }

    /**
     * Returns the database field name from the given model field name.
     * If the database field is not mapped, returns the given database field name.
     * @param string The model field name, or the database field name if not mapped.
     * @return string The databse field name.
     */
    function modelfield($dbfield) {
        $modelfield = @array_search($dbfield, $this->mapping);
        return $modelfield ? $modelfield : $dbfield;
    }

    /**
     * Returns an associative array contanining params values
     * with translated keys (e.g. keys reflect the db fields names).
     * Return array structure:
     * <code>
     * array(
     *     'db_fieldname_1' => array(
     *         'value' => 'some value',
     *         'wildcard' => false
     *      ),
     *      'db_fieldname_2' => array(
     *         'value' => '%some value%',
     *         'wildcard' => true
     *      ),
     *      ...
     * )
     * </code>
     * - Only mapped fields are taken into account.
     * - Wildcard filters patterns are applied to params values if applicable.
     * @param bool If true, applies the filters pattern be applied to value
     * @return array
     */
    function fields_values($no_primary = false) {
        $mapping = array();
        foreach ($this->params as $paramname => $paramvalue) {
            if (!array_key_exists($paramname, $this->mapping)) continue;
            // If applicable, skips field if it is a primary key field
            if ($no_primary && in_array($paramname, $this->primary)) continue;
            $field = $this->mapping[$paramname];
//            if ($paramvalue === '') $paramvalue = 'NULL';
            $mapping[$field] = $paramvalue;
        }
        return $mapping;
    }

    /**
     * Return the given value, escaped and quoted.
     * If the field name (modelfield) is given and the
     * field is listed in self::constants array,
     * and the value is a sql constant,
     * returns the value unquoted and unescaped.
     * @param string The value to escape and enquote
     * @param string Optional field name (modelfield)
     * @return string
     */
    function escape($value, $field = null) {
        if (is_null($value)) {
            return 'NULL';
        } else if ((!$this->constants || in_array($field, $this->constants)) && in_array($value, $this->sql_constants())) {
            return $value;
        }
        return "'".mysql_real_escape_string($value)."'";
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
     * Returns a default SQL SELECT clause.
     * @return string
     */
    function sql_select() {
        $fragments = xUtil::arrize($this->return);
        // Replaces * with all fields
        foreach ($fragments as $key => $fragment) {
            if ($fragment != '*') continue;
            unset($fragments[$key]);
            foreach ($this->mapping as $model_field => $db_field) {
                $fragments[] = "`{$this->maintable}`.`{$db_field}` AS `{$model_field}`";
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
                $fragments[] = "`{$model->maintable}`.`{$db_field}` AS `{$model_name}_{$model_field}`";
            }
        }
        return " SELECT ".implode(', ', $fragments);
    }

    /**
     * Returns a default SQL FROM clause.
     * @return string
     */
    function sql_from() {
        return " FROM `{$this->maintable}`";
    }

    /**
     * Returns a default SQL WHERE clause.
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
     * Returns a default SQL JOIN clause to be used as a join.
     * @return string
     */
    function sql_join() {
        $joins = xUtil::filter_keys($this->joins, xUtil::arrize(@$this->params['xjoin']));
        return implode($joins, ' ');
    }

    /**
     * Returns a default SQL ORDER clause.
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
     * Returns a default SQL GROUP BY clause.
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
     * Returns default SQL LIMIT and/or OFFSET clause(s).
     * @return string
     */
    function sql_limit() {
        $sql = '';
        if (@$this->params['xlimit']) $sql .= " LIMIT {$this->params['xlimit']}";
        if (@$this->params['xoffset']) $sql .= " OFFSET {$this->params['xoffset']}";
        return $sql;
    }

    /**
     * Exectues the given sql and returns its query result.
     *  - If the result is empty, an empty array is returned.
     *  - If the result is not empty, an 2 dimensional associative array
     *    with mapped array keys containing the result rows is returned.
     *  - If the query didn't return row results
     *    (e.g. insert, update and delete queries),
     *    an informational array is returned, containg
     *     - the last insert id,
     *     - the number of affected rows,
     *     - additional mysql information,
     *     - and the raw mysql result.
     * @param string The SQL statement to execute.
     * @return array
     */
    function query($sql) {
        $db = xContext::$db;
        // Executes query
        xContext::$log->log("Executing query: \n{$sql}", $this);
        $qr = mysql_query($sql, $db);
        if (!$qr) throw new xException("Invalid query: $sql # " . mysql_error($db));
        // Creates an array of results
        if (is_resource($qr)) {
            // Returns an empty array if no row was retrieved
            if (mysql_num_rows($qr) < 1) return array();
            // Translates db fields into model fields,
            // keeping the result field name if not found in mapping
            $fields = array();
            foreach(array_keys(mysql_fetch_assoc($qr)) as $dbfield) {
                $fields[] = $this->modelfield($dbfield);
            }
            mysql_data_seek($qr, 0);
            // Creates the result array
            $result = array();
            while ($row = mysql_fetch_assoc($qr)) {
                $result[] = array_combine($fields, $row);;
            }
        } else {
            $result = array(
                'insertid' => mysql_insert_id($db),
                'affectedrows' => mysql_affected_rows($db),
                'info' => mysql_info($db),
                'raw' => $qr
            );
        }
        if (is_resource($qr)) mysql_free_result($qr);
        return $result;
    }
}

?>
