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
abstract class xModelMysql extends xModel {

    /**
     * @see xModel::get()
     * @return array
     */
    function get($rownum=null) {
        $sql = implode("\n", array(
            $this->sql_select(),
            $this->sql_from(),
            $this->sql_join(),
            $this->sql_where(),
            $this->sql_group(),
            $this->sql_order(),
            $this->sql_limit()
        ));
        // Manages return format
        $result = $this->query($sql);
        // Returns only the record corresponding to rownum.
        // If the record doesn't exist, returns an empty array
        if (!is_null($rownum)) return @$result[$rownum] ? $result[$rownum] : array();
        else return $result;
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
        // Automagically sets the modified field if applicable
        if (isset($this->mapping['modified'])) {
            $updates[] = "modified = ".$this->escape('CURRENT_TIMESTAMP', 'modified', true);
        }
        // Creates final sql
        $sql = implode('', array(
            "UPDATE `{$this->maintable}` SET\n\t",
            implode(",\n\t", $updates),
            "\n",
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
        foreach ($this->fields_values() as $field => $value) {
            $sqlF[] = $field;
            $sqlV[] = $this->escape($value, $this->modelfield($field));
        }
        // Automagically sets the created field if applicable
        if (isset($this->mapping['created'])) {
            $sqlF[] = 'created';
            $sqlV[] = $this->escape('CURRENT_TIMESTAMP', 'created', true);
        }
        // Creates final sql
        $sql = "INSERT INTO `{$this->maintable}`".
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
        $sql = "DELETE FROM {$this->maintable}"."\n".$this->sql_where(false, true);
        return $this->query($sql);
    }

    /**
     * @see xModel::count()
     * @return int
     */
    function count() {
        $primary = $this->primary();
        $sql = implode("\n", array(
            "SELECT count(`{$this->maintable}`.`{$primary}`) as count",
            $this->sql_from(),
            $this->sql_join(),
            $this->sql_where(),
            $this->sql_group(),
            $this->sql_order(),
            $this->sql_limit()
        ));
        $count = array_shift($this->query($sql));
        return $count['count'];
    }

    /**
     * @param mixed The value to escape.
     * @param string The model field name related to the given value.
     * @param bool True to allow SQL constants.
     * @see xModel::escape()
     * @return string
     */
    function escape($value, $field = null, $allow_constants = false) {
        if (is_null($value) || $value === '') {
            return 'NULL';
        } else if (is_array($value)) {
            $values = array();
            foreach ($value as $v) $values[] = $this->escape($v, $field);
            return $values ? '('.implode(',', $values).')' : '(NULL)';
        } else if (($allow_constants || !$this->constants || in_array($field, $this->constants)) && in_array($value, $this->sql_constants())) {
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
     * @see xModel::sql_select()
     * @return string
     */
    function sql_select() {
        // Creates SELECT for all fields (local and foreign)
        // - Local fields
        foreach ($this->mapping as $model_field => $db_field) {
            $allfragments[] = "`{$this->maintable}`.`{$db_field}` AS `{$model_field}`";
        }
        // - Foreign fields
        //   (replaces joined tables db fields name with model fields names)
        foreach ($this->foreign_mapping() as $modelfield => $dbfield) {
            // Enquotes tablename and fieldname
            $dbfield = preg_replace('/^(\w*)\.(\w*)$/', '`$1`.`$2`', $dbfield);
            // Creates SQL SELECT fragments
            $allfragments[] = "{$dbfield} AS `{$modelfield}`";
        }
        $all = implode(",\n\t", $allfragments);
        // Replaces '*' with all-fields-SELECT
        $fragments = xUtil::arrize($this->return);
        foreach ($fragments as &$fragment) {
            $fragment = preg_replace('/^\*$/', $all, $fragment);
        }
        // Manages specified modelfield names in xreturn parameter:
        // - Substitutes local field names
        // - Substitutes foreign field names
        // - Substitutes unknown field names
        $local_mapping = $this->mapping;
        $foreign_mapping = $this->foreign_mapping();
        // - Creates a mapping for substitution, giving priority to foreign models
        $mapping = array_merge($local_mapping, $foreign_mapping);
        uksort($mapping, function($a, $b) { return strlen($b) - strlen($a); });
        foreach ($fragments as $key => &$fragment) {
            if (in_array($fragment, $local_mapping)) {
                // Substitutes 'modelfield'
                // with 'tablename.dbfield AS modelfield'
                $fragment = "`{$this->maintable}`.`{$fragment}` AS `{$fragment}`";
            } elseif (in_array($fragment, array_keys($foreign_mapping))) {
                // Substitutes foreign 'modelfield'
                // with 'foreign_tablename.dbfield AS modelfield'
                $fragment = "{$foreign_mapping[$fragment]} AS `{$fragment}`";
                // Enquotes table.field names
                $fragment = preg_replace('/(\w*)\.(\w*)/', '`$1`.`$2`', $fragment);
            } else {
                // Substitutes unknown modelfields:
                // - Skips if statement is complete (eg. dbfield AS modelfield)
                if (stripos($fragment, ' AS ')) continue;
                // - This pattern matches a complete modelfield name
                //   (eg. does not match 'personne_id' if searching for 'id')
                $pattern = "/(.*[^\w.`])(%s)([^\w`].*)/";
                // - Foreign fields substitution
                foreach ($mapping as $modelfield => $dbfield) {
                    $search = sprintf($pattern, $modelfield);
                    // Enquotes table.field name (if any)
                    $dbfield = preg_replace('/(\w*)\.(\w*)/', '`$1`.`$2`', $dbfield);
                    // Replace backrefs:
                    // $1=fieldname-prejunk, $3=fieldname-postjunk
                    $replace = (strpos($dbfield, '.') !== false) ?
                        // Replace for foreign fields
                        "$1{$dbfield}$3 AS `{$modelfield}`" :
                        // Replace for local fields
                        "$1`{$this->maintable}`.`{$dbfield}`$3 AS `{$modelfield}`";
                    $fragment = preg_replace($search, $replace, $fragment);
                }
            }
        }
        // Returns the SELECT statement
        return "SELECT ".implode(",\n\t", $fragments);
    }

    /**
     * @see xModel::sql_from()
     * @return string
     */
    function sql_from() {
        return "FROM `{$this->maintable}`";
    }

    /**
     * @see xModel::sql_where()
     * @param bool True to consider primary key field(s) only
     * @param bool True to consider local fields only (ignoring foreign tables fields)
     * @return string
     */
    function sql_where($primary_only = false, $local_only = false) {
        if ($this->where) return $this->sql_where_from_template();
        $where = $this->sql_where_prepare($primary_only, $local_only);
        $lines = array();
        // Sets WHERE 1=0 if the 1st where clause is OR
        $first_predicate = array_shift(array_slice($where, 0, 1));
        $first_operator = $first_predicate['operator'];
        $lines[] = strtoupper($first_operator) == 'OR' ?  'WHERE 1=0' : 'WHERE 1=1';
        // Creates sql where clause contents
        foreach ($where as $i) {
            // Manages comparator
            if (is_array($i['value'])) $i['comparator'] = 'IN';
            elseif ($this->escape($i['value']) == 'NULL') $i['comparator'] = 'IS';
            // Manages value
            $i['value'] = $this->escape($i['value'], $this->modelfield($i['field']));
            // Create SQL clause
            $lines[] = "{$i['operator']} `{$i['table']}`.`{$i['field']}` {$i['comparator']} {$i['value']}";
        }
        return implode("\n\t", $lines);
    }
    function sql_where_from_template($primary_only = false, $local_only = false) {
        $where = $this->sql_where_prepare($primary_only, $local_only);
        $tpl = @$this->wheres[$this->where];
        if (!$tpl) throw new xException("Where template not found or empty ('{$this->where}')");
        // Replace regular field names ({{x}}) and values ({x})
        foreach ($where as $i => $predicate) {
            $modelfield = $this->modelfield($predicate['field']);
            $value = $this->escape($predicate['value'], $modelfield);
            $tpl = str_replace("{{{$modelfield}}}", $predicate['field'], $tpl, $count1);
            $tpl = str_replace("{{$modelfield}}", $value, $tpl, $count2);
            // Already replaced fields will not be reused in loops below
            if ($count1 || $count2) unset($where[$i]);
        }
        // Replaces loops ([]), if applicable
        preg_match_all('/\[(.*?)\]/', $tpl, $matches);
        list($replaces, $patterns) = $matches;
        for ($i=0; $i<count($replaces); $i++) {
            $pattern = $patterns[$i];
            $replace = $replaces[$i];
            $sql = array();
            foreach ($where as $modelfield => $predicate) {
                $sql[] = str_replace(
                    array(
                        '{{*}}',
                        '{*}'
                    ),
                    array(
                        "`{$predicate['table']}`.`{$predicate['field']}`",
                        $this->escape($predicate['value'], $modelfield)
                    ),
                    $pattern
                );
            }
            $tpl = str_replace($replace, implode($sql, ' '), $tpl);
        }
        return "WHERE {$tpl}";
    }

    /**
     * @see xModel::sql_join()
     * @return string
     */
    function sql_join() {
        return "\t".implode($this->joins(), "\n\t");
    }

    /**
     * TODO: allow to order_by foreign fields
     * @see xModel::sql_order()
     * @return string
     */
    function sql_order() {
        $sql = '';
        if ($this->order_by) {
            $fields = array();
            // Substitues modelfields with dbfields if possible,
            // else keep field as is
            foreach(xUtil::arrize($this->order_by) as $field) {
                $dbfield = $this->dbfield($field);
                $fields[] = in_array($field, $this->mapping) ?
                    "`{$this->maintable}`.`{$dbfield}`" : $field;
            }
            $order = $this->order ? $this->order : 'ASC';
            $sql = ' ORDER BY '.implode(',', $fields)." {$order}";
        }
        return $sql;
    }

    /**
     * TODO: allow to group_by foreign fields
     * @see xModel::sql_group()
     * @return string
     */
    function sql_group() {
        $sql = '';
        if ($this->group_by) {
            $fields = array();
            // Substitues modelfields with dbfields if possible,
            // else keep field as is
            foreach(xUtil::arrize($this->group_by) as $field) {
                $dbfield = $this->dbfield($field);
                $fields[] = in_array($field, $this->mapping) ?
                    "`{$this->maintable}`.`{$dbfield}`" : $field;
            }
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
        $qr = $this->q($sql);
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
            $db = xContext::$db;
            $result = xUtil::array_merge(
                array(
                    'xsuccess' => true,
                    'xinsertid' => mysql_insert_id($db),
                    'insertid' => mysql_insert_id($db), // For retro-compatibility
                    'xaffectedrows' => mysql_affected_rows($db),
                    'xinfo' => mysql_info($db),
                    'xraw' => $qr
                )
            );
        }
        if (is_resource($qr)) mysql_free_result($qr);
        return $result;
    }

    /**
     * Executes the given sql and returns the raw result resource, or throws an exception on error.
     * @see www.php.net/manual/function.mysql-query.php
     * @return resource
     */
    static function q($sql) {
        $db = xContext::$db;
        $class = get_called_class();
        // Executes query
        xContext::$log->log("Executing query: \n{$sql}", $class);
        $qr = mysql_query($sql, $db);
        if (!$qr) {
            $mysql_error = mysql_error($db);
            $message = xContext::$error_reporting ?
                "Invalid query: $sql # {$mysql_error}" :
                "Invalid query: [query obfuscated] # {$mysql_error}";
            throw new xException($message, 500);
        }
        return $qr;
    }
}

?>
