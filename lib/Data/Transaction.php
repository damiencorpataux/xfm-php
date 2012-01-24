<?php

class xTransaction {

    static $started_transactions_count = 0;

    static $autocommit_state_backup = null;

    var $last_insert_id = null;

    var $results = array();
    var $exceptions = array();

    function __construct() {}

    static function q($sql) {
        return xModel::q($sql);
    }

    /**
     * Starts a new transaction if no transaction already started.
     * Otherwise, manages nested transaction as a single transaction.
     */
    function start() {
        // Manages nested transactions:
        // only issues a BEGIN statement for the first transaction
        if (self::$started_transactions_count < 1) {
            // Backups current autocommit state
            self::$autocommit_state_backup = $this->autocommit();
            // Sets autocommit state to false
            $this->autocommit(false);
            // Begin transaction
            $this->q('BEGIN');
        }
        // Manages transactions counter
        self::$started_transactions_count++;
    }

    function commit() {
        if (self::$started_transactions_count < 1) {
            throw new xException('Cannot commit when no transaction in progress', 500);
        } else {
            $this->q('COMMIT');
            self::$started_transactions_count--;
        }
    }

    function rollback() {
        $this->q('ROLLBACK');
        self::$started_transactions_count = 0;
        $this->autocommit(self::$autocommit_state_backup);
        return $this->summary();
    }

    /**
     * Ends the current transaction (COMMIT or ROLLBACK according errors).
     * If nested, the transaction is not COMMITed until the last top-level transaction is ended.
     * @throw xException Throw an exception if exceptions occured
     * @return Transaction summary (xModel compatible)
     */
    function end() {
        // Commit or rollback according occured exceptions
        if ($this->exceptions) {
            $this->rollback();
            $this->throw_exception();
        } else {
            $this->commit();
        }
        // Restores autocommit state
        if (self::$started_transactions_count < 1) $this->autocommit(self::$autocommit_state_backup);
        // Returns current summary
        return $this->summary();
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