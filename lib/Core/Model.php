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
        return parent::get();
    }

    /**
     * Implements the REST post method to access data.
     * Issues a UPDATE and returns the result
     * as an associative array.
     * @see xModel::query()
     * @return array
     */
    function post() {
        return parent::posts();
    }

    /**
     * Implements the REST put method to access data.
     * Issues a INSERT and returns the result
     * as an associative array.
     * @see xModel::query()
     * @return array
     */
    function put() {
        return parent::put();
    }

    /**
     * Implements the REST delete method to access data.
     * Issues a DELETE and returns the result
     * as an associative array.
     * @see xModel::query()
     * @return array
     */
    function delete() {
        return parent::delete();
    }

    /**
     * Issues a COUNT and returns the result
     * @see xModel::query()
     * @return int
     */
    function count() {
        throw new xException('Not implemented', 501);
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
    abstract function escape($value, $field = null);
    abstract function sql_constants();

    /**
     * Returns a default SQL SELECT clause.
     * @return string
     */
    abstract function sql_select();

    /**
     * Returns a default SQL FROM clause.
     * @return string
     */
    abstract function sql_from();

    /**
     * Returns a default SQL WHERE clause.
     * @return string
     */
    abstract function sql_where($primary_only = false);

    /**
     * Returns a default SQL JOIN clause to be used as a join.
     * @return string
     */
    abstract function sql_join();

    /**
     * Returns a default SQL ORDER clause.
     * @return string
     */
    abstract function sql_order();

    /**
     * Returns a default SQL GROUP BY clause.
     * @return string
     */
    abstract function sql_group();

    /**
     * Returns default SQL LIMIT and/or OFFSET clause(s).
     * @return string
     */
    abstract function sql_limit();

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
    abstract function query($sql);
}

?>
