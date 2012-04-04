<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * LICENSE
 * This library is licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * NOTE: This is event not alpha: just committed because developped on a sneaky computer.
 * This class will be able to generate sql.
 * @package xFreemwork
**/
class xSql {

    var $operation = 'select';
    var $operations_available = array('insert', 'select', 'update', 'delete');
    /**
     * Form:
     * array(
     *     'field1' = array('value1'),
     *     'field2' = array('value2', 'value3', 'value4'),
     * )
     *
     */
    var $values = array();
    /**
     * Form:
     * array(
     *     'table1',
     *     'alias1' => 'table2'
     *     new xSql(...),
     *     'alias2' => new xSql(...)
     * )
     */
    var $from = array();
    var $join = array();
    /**
     * <code>
     * array(
     *     'field1',
     *     array('field2', 'field3'),
     *     array(
     *       'field10',
     *       'or' => array('field3', 'field1'),
     *       'or' => 'field11',
     *       array('field4', 'field5', 'field6')
     *     ),
     * );
     * </code>
     * Would result in:
     * field1 = 'value'
     * AND (field1 = 'value' AND field1 = 'value')
     * AND (
     *     field10 = 'value'
     *     OR (field3 = 'value' AND field1 = 'value')
     *     OR field11 = 'value'
     *     AND (field4 = 'value' AND field5 = 'value' AND field6 = 'value')
     * )
     */
    var $where = array();
    var $where_groups = array();
    var $group = array();
    var $order_by = array();
    var $order_dir = 'ASC';
    var $offset = array();
    var $limit = array();

    abstract public function dump();

    abstract function escape($value);

    abstract function quote_value($value);

    abstract function quote_field($name);

    abstract function process_field($name);

    protected function batchize($batch_or_x, $y) {
        $batch = (is_array($batch_or_x)) ?
            $batch_or_x : array($batch_or_x => $y);
    }

    function values($batch_or_field, $value) {
        // TODO: args validation
        $batch = $this->batchize($batch_or_field, $value);
        foreach ($batch as $field => $value)
            $this->from[$field][] = $value;
    }

    function from($batch_or_table, $alias=null) {
        // TODO: args validation
        $batch = $this->batchize($batch_or_table, $alias);
        foreach ($batch as $table => $alias)
            is_null($alias) ?
                $this->from[] = $table : $this->from[$alias] = $table;
    }

    /**
     *
     * @param string (Optional) Type of the join (inner, outer, left, right, natural)
     */
    function join($batch_or_table, $local_fields, $foreign_field, $type=null) {
        $local_fields = is_array($local_fields)) ?
            $local_fields : array($local_fields);
        $foreign_fields = is_array($foreign_fields)) ?
            $foreign_fields : array($foreign_fields);
        $batch = $this->batchize(
            $batch_or_table, array(
                'local' => $local_fields
                'foreign' => $foreign_fields
            )
        );
        foreach ($batch as $table => $fields)
            $this->join[] = $this->join[$table] = $fields;
        }
    }

    function where($batch_or_field, $values) {
        $values = is_array($values)) ?
            $values : array($values);
        $batch = $this->batchize($batch_or_field, $values);
        foreach ($batch as $field => $values)
            $this->where[$field][] = $values;
    }

    function group($fields) {
        $fields = is_array($fields)) ?
            $fields : array($fields);
        foreach ($fields as $field)
            $this->group[$field] = true;
    }

    function order($fields) {
        $fields = is_array($fields)) ?
            $fields : array($fields);
        foreach ($fields as $field)
            $this->order[$field] = true;
    }

    function offset($value) {
        $this->offset = $value;
    }

    function limit($value) {
        $this->limit = $limit;
    }

    function __toString() {
        return $this->dump();
    }
}
