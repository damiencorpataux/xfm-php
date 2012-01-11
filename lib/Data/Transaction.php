<?php

class xTransaction {

    var $autocommit_state_backup = null;

    var $last_insert_id = null;

    var $results = array();
    var $exceptions = array();

    function __construct() {}

    static function q($sql) {
        return xModel::q($sql);
    }

    function start() {
        // Backups current autocommit state
        $this->autocommit_state_backup = $this->autocommit();
        // Sets autocommit state to false
        $this->autocommit(false);
        // Begin transaction
        $this->q('BEGIN');
    }

    function commit() {
        $this->q('COMMIT');
    }

    function rollback() {
        $this->q('ROLLBACK');
    }

    function end() {
        // Commit or rollback according occured exceptions
        if ($this->exceptions) $this->rollback();
        else $this->commit();
        // Restores autocommit state
        $this->autocommit($this->autocommit_state_backup);
        // Throws an exception if errors occured
        if ($this->exceptions) $this->throw_exception();
        else return $this->summary();
    }

    /**
     * Shorthand for execute_model()
     * @see execute_model()
     */
    function execute($model_instance, $method_name, $method_args = array()) {
        return $this->execute_model($model_instance, $method_name, $method_args);
    }

    function execute_model($model_instance, $method_name, $method_args = array()) {
        // Resets last insert id value
        $this->last_insert_id = null;
        // Executes model method
        try {
            // Calls the givem model method
            $result = call_user_func_array(
                array($model_instance, $method_name),
                $method_args
            );
            // Creates a result array for the operation
            $this->results[] = xUtil::array_merge(
                array('xmodel' => $model_instance->name),
                array('xparams' => $model_instance->params),
                array('result' => $result)
            );
            // Latches the last insert id
            $this->last_insert_id = $result['xinsertid'];
            // Returns the result
            return $result;
        } catch (Exception $e) {
            $this->exceptions[] = $e;
            return $e;
        }
    }

    function execute_sql($sql) {
        $sql = xUtil::arrize($sql);
        // Executes query/ies
        foreach ($sql as $sql_statement) {
            try {
                $result = $this->results[] = $this->q($sql_statement);
                $this->last_insert_id = mysql_insert_id(xContext::$db);
                return $result;
            } catch (Exception $e) {
                $this->exceptions[] = $e;
                return $e;
            }
        }
    }

    function summary() {
        // Computes the total number of affected rows accross queries
        $affected_rows = 0;
        foreach ($this->results as $result) $affected_rows += $result['result']['xaffectedrows'];
        // Creates a result set according xModel::query result
        $results = array(
            'xsuccess' => empty($this->exceptions),
            'xaffectedrows' => $affected_rows,
            'xresults' => $this->results
        );
        return $results;
    }

    /**
     * Returns the last execute() insert id.
     * Returns null if no last insert id available.
     * @return int The insert id of the last execute().
     */
    function insertid() {
        return $this->last_insert_id;
    }

    function throw_exception() {
        $error_count = count($this->exceptions);
        // Enhances exception data with sql query error message, if applicable
        if (xContext::$config->error->reporting == 'E_ALL')
            foreach ($this->exceptions as $exception)
                $exception->data = $exception->getMessage();
        throw new xException(
            "{$error_count} operation(s) failed during the transaction",
            500,
            array(
                'exceptions' => $this->exceptions,
                'results' => $this->results
            )
        );
    }

    static function autocommit($value = null) {
        // No $value specified, returns the current autocommit state
        if (is_null($value))
            return array_shift(mysql_fetch_assoc(self::q('SELECT @@autocommit')));
        // Sets autocommit state to $value
        $value = $value ? '1' : '0';
        $success = self::q("SET AUTOCOMMIT={$value}");
        if (!$success) throw new xException('Could not set autocommit state');
    }
}