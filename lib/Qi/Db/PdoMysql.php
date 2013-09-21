<?php
/**
 * Pdo Mysql class file 
 *
 * @package Qi
 * @subpackage Db
 */

/**
 * Qi Db PdoMysql class
 * 
 * @package Qi
 * @subpackage Db
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.1
 */
class Qi_Db_PdoMysql
{
    /**#@+
     * Constants for Errinfo()
     *
     * @var int
     */
    const ERRINFO_SQLSTATE_CODE = 0;
    const ERRINFO_ERROR_CODE = 1;
    const ERRINFO_ERROR_MESSAGE = 2;
    /**#@-*/

    /**
     * Db Config settings
     *
     * @var array
     */
    protected $_cfg;

    /**
     * The database resource object
     *
     * @var object
     */
    protected $_conn;

    /**
     * Array of errors encountered
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Constructor
     *
     * @param array $dbcfg Database configuration data
     * @return void
     */
    public function __construct($dbcfg)
    {
        $cfgDefaults = array(
            'log'      => false,
            'log_file' => '',
            'host'     => '',
            'db'       => '',
            'user'     => '',
            'pass'     => '',
        );

        $this->_cfg = array_merge($cfgDefaults, (array) $dbcfg);

        try {
            $this->_conn = new PDO(
                'mysql:host=' . $this->_cfg['host']
                . ';dbname=' . $this->_cfg['db'],
                $this->_cfg['user'],
                $this->_cfg['pass']
            );
        } catch (Exception $exception) {
            echo $exception->getMessage() . "\n";
        }

        if (!$this->_conn) {
            echo "PdoMysql connection error.\n";
        }
    }

    /**
     * Safely execute a sql query statement
     *
     * @param string $q The sql query statement
     * @param array $data Data to be bound in query
     * @return array|bool The resulting data or false
     */
    public function safeQuery($q, $data = null)
    {
        // Log the sql statement if logging is enabled
        $this->log($q, 'SQL');
        if (!empty($data)) {
            $this->log(print_r($data, 1), 'DATA');
        }

        // Prepare the query
        $statement = $this->_conn->prepare($q);

        if (!$statement) {
            $this->_logPdoError($this->_conn->errorInfo());
            return false;
        }

        // Execute the query
        if (!empty($data)) {
            $status = $statement->execute($data);
        } else {
            $status = $statement->execute();
        }

        if (!$status) {
            $this->_logPdoError($statement->errorInfo());
        }

        return $statement;
    }

    /**
     * Insert a row into a table
     * 
     * @param string $table Name of table
     * @param array $data Associative array with data to insert
     * @return int|bool
     */
    public function insert($table, $data)
    {
        $keys         = array_keys($data);
        $placeholders = array_fill(0, count($keys), "?");

        $set = "(" . implode(',', $keys) . ") "
            . "VALUES (" . implode(',', $placeholders) . ")";

        $data = array_values($data);
        if ($this->safeInsert($table, $set, $data)) {
            return $this->_conn->lastInsertId();
        }

        return false;
    }

    /**
     * Safely insert rows into a table
     *
     * @param string $table The table name
     * @param string $set The set part of the query e.g. "VALUES (...)"
     * @param mixed $data Optional data to bind to prepared statement
     * @return bool Whether the statement executed successfully
     */
    public function safeInsert($table, $set, $data = null)
    {
        $q = "INSERT INTO $table $set";

        if ($r = $this->safeQuery($q, $data)) {
            return true;
        }

        return false;
    }

    /**
     * Safely update row or rows in a table
     *
     * @param string $table The table name
     * @param string $set The set part of the query e.g. "col='value'"
     * @param string $where The where clause
     * @param mixed $data Optional data to bind to prepared statement
     * @return bool Whether the statement executed successfully
     */
    public function safeUpdate($table, $set, $where, $data = null)
    {
        $q = "UPDATE $table SET $set WHERE $where";
        if ($r = $this->safeQuery($q, $data)) {
            return true;
        }
        return false;
    }

    /**
     * Update a row
     * 
     * @param string $table Table name
     * @param array $data Associative array of data
     * @param string $where Where clause content
     * @return mixed
     */
    public function update($table, $data, $where)
    {
        $set = array();
        foreach ($data as $name => $value) {
            $set[] = "$name=?";
        }
        $set = implode(',', $set);

        $data = array_values($data);

        return $this->safeUpdate($table, $set, $where, $data);
    }

    /**
     * Safely delete a row or rows from a table
     *
     * @param string $table The table name
     * @param string $where The where clause
     * @param mixed $data Optional data to bind to prepared statement
     * @return bool Whether the statement executed successfully
     */
    public function safeDelete($table, $where, $data = null)
    {
        $q = "DELETE FROM $table WHERE $where";

        if ($r = $this->safeQuery($q, $data)) {
            return true;
        }

        return false;
    }

    /**
     * Safely get a thing from a table based on a criteria
     *
     * @param string $column The column name to extract
     * @param string $table The table name
     * @param string $where The where clause
     * @return mixed The data or false
     */
    public function safeField($column, $table, $where)
    {
        $q = "SELECT $column FROM $table WHERE $where";
        $r = $this->safeQuery($q);
        if ($r->rowCount() > 0) {
            return $r->fetchSingle();
        }
        return false;
    }

    /**
     * Safely extract column values from a row or rows
     *
     * @param string $column The thing to extract
     * @param string $table the table name
     * @param string $where The where clause
     * @return string|array A comma separated list of the values returned
     *                      or an empty array
     */
    public function safeColumn($column, $table, $where)
    {
        $q  = "SELECT $column FROM $table WHERE $where";
        $rs = $this->getRows($q);
        if ($rs) {
            $out = array();
            foreach ($rs as $a) {
                $out[] = implode(",", $a);
            }
            return $out;
        }
        return array();
    }

    /**
     * Safely get a row from a table
     *
     * @param string $columns Comma separated list of columns to return
     * @param string $table The table name
     * @param string $where The where clause
     * @return array The row or an empty array
     */
    public function safeRow($columns, $table, $where)
    {
        $q  = "SELECT $columns FROM $table WHERE $where";
        $rs = $this->getRow($q);
        if ($rs) {
            return $rs;
        }
        return array();
    }

    /**
     * Safely get rows from a table
     *
     * @param string $columns The columns to return
     * @param string $table The table name
     * @param string $where The where clause
     * @return array The rows or an empty array
     */
    public function safeRows($columns, $table, $where)
    {
        $q  = "SELECT $columns FROM $table WHERE $where";
        $rs = $this->getRows($q);
        if ($rs) {
            return $rs;
        }
        return array();
    }

    /**
     * Get a count of rows
     *
     * @param string $table The table name
     * @param string $where The where clause
     * @return string The number of rows
     */
    public function safeCount($table, $where)
    {
        return $this->getThing(
            "SELECT count(*) FROM $table WHERE $where"
        );
    }

    /**
     * Safely alter a table
     *
     * @param string $table The table name
     * @param string $alter The alter part of statement e.g. "ADD COLUMN ... "
     * @return bool Whether the statement executed successfully
     */
    public function safeAlter($table, $alter)
    {
        $q = "ALTER TABLE $table $alter";
        if ($r = $this->safeQuery($q)) {
            return true;
        }
        return false;
    }

    /**
     * Safely optimize a table
     *
     * @param string $table The table name
     * @return bool Whether the statement executed successfully
     */
    public function safeOptimize($table)
    {
        $this->log("Optimize is not available for sqlite.", "Warning");
       
        return false;
    }

    /**
     * Safely repair a table
     *
     * @param string $table The table name
     * @return bool Whether the statement executed successfully
     */
    public function safeRepair($table)
    {
        $this->log("Repair is not available for sqlite.", "Warning");

        return false;
    }

    /**
     * Fetch a value for a specific condition
     *
     * @param string $col The column to return
     * @param string $table The table name
     * @param string $key The column to test for the condition
     * @param string $val The value to test for in column $key
     * @return mixed The first row matching the query or false
     */
    public function fetch($col, $table, $key, $val)
    {
        $q = "SELECT $col FROM $table where $key = "
            . $this->_conn->quote($val) . " limit 1;";

        if ($r = $this->safeQuery($q)) {
            $row = $r->fetch(PDO::FETCH_ASSOC);
            if (isset($row[$col])) {
                return $row[$col];
            } else {
                return null;
            }
        }

        return null;
    }

    /**
     * Execute a sql query and return the first resulting row
     *
     * @param string $query The sql query statement
     * @param array $data Data to be bound in query
     * @param int $indices The array indices returned
     *                     (SQLITE_NUM, SQLITE_ASSOC, SQLITE_BOTH)
     * @return array|bool The resulting row or null
     */
    public function getRow($query, $data = null, $indices=PDO::FETCH_ASSOC)
    {
        if ($r = $this->safeQuery($query, $data)) {
            return $r->fetch($indices);
        }
        return null;
    }

    /**
     * Execute a sql query and return the resulting rows
     *
     * @param string $query The sql query statement
     * @param array $data Data to be bound in query
     * @param int $indices The array indices returned
     *                     (SQLITE_NUM, SQLITE_ASSOC, SQLITE_BOTH)
     * @return array|bool The resulting rows or false
     */
    public function getRows($query, $data = null, $indices=PDO::FETCH_ASSOC)
    {
        if ($r = $this->safeQuery($query, $data)) {
            return $r->fetchAll($indices);
        }
        return false;
    }

    /**
     * Execute a sql query and return the first column in the resulting row
     *
     * @param string $query The sql query statement
     * @return mixed The resulting thing or null
     */
    public function getThing($query)
    {
        if ($r = $this->safeQuery($query)) {
            $row = $r->fetch(PDO::FETCH_NUM);
            if (isset($row[0])) {
                return $row[0];
            } else {
                return null;
            }
        }
        return null;
    }

    /**
     * Return values of one column from multiple rows in an num indexed array
     *
     * @param string $query The sql statement
     * @return void
     */
    public function getThings($query)
    {
        $rs = $this->getRows($query, PDO::FETCH_NUM);

        $out = array();

        if ($rs) {
            foreach ($rs as $a) {
                $out[] = $a[0];
            }
        }

        return $out;
    }

    /**
     * Get a count of rows meeting a criteria
     *
     * @param string $table The table name
     * @param string $where The where clause
     * @return string The resulting number of rows
     */
    public function getCount($table, $where)
    {
        return $this->getThing(
            "select count(*) from $table where $where"
        );
    }

    /**
     * Creates an insert string from an array of col=>value.
     *
     * @param string $table The name of the table
     * @param data $a The array of data for which to get a string
     * @return string The sql string
     * @deprecated Use insert() instead
     */
    public function get_insert_string($table, $a)
    {
        $q_cols = '';
        $q_vals = '';

        foreach ($a as $col=>$value) {
            $q_cols .= "$col,";
            $q_vals .= $this->_conn->quote($value) . ",";
        }

        $q_cols = substr($q_cols, 0, -1);
        $q_vals = substr($q_vals, 0, -1);

        $q = "INSERT INTO $table ("
            .$q_cols . ") "
            ."VALUES (" . $q_vals . ")";
        return $q;
    }

    /**
     * Creates an update string from an array of col=>value and a where clause.
     *
     * @param string $table The name of the table
     * @param array $a The array of data for which to get the string
     * @param string $where The where clause
     * @return string The sql string
     */
    public function get_update_string($table, $a, $where)
    {
        if (!$where) {
            // enforce where clause to prevent unwanted data loss.
            return false;
        }
        $q_text = '';

        foreach ($a as $col => $value) {
            $q_text .= $col . '=' . $this->_conn->quote($value) . ", ";
        }

        $q_text = substr($q_text, 0, -2);

        $q = "UPDATE $table SET "
            . $q_text . " "
            . "WHERE " . $where;
        return $q;
    }

    /**
     * Do a safe query, return lastInsertId if successful.
     *
     * @param string $q The sql statement
     * @return mixed The insert id or error message
     */
    public function doSafeQuery($q)
    {
        if ($result = $this->safeQuery($q)) {
            return $this->_conn->lastInsertId();
        } else {
            $err = $this->_conn->errorInfo();
            return $err[0] . ": " . $err[2];
        }
    }

    /**
     * Escape string for sqlite use
     *
     * @param string $string The string to be escaped
     * @return string The sanitized string
     * @deprecated You should just use quote()
     */
    public function escape($string)
    {
        if (!function_exists('sqlite_escape_string')) {
            return str_replace("'", "''", $string);
        }
        return sqlite_escape_string($string);
    }

    /**
     * Magic call method to pass down to db object
     * 
     * @param string $method Method name
     * @param array $args Arguments
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_conn, $method), $args);
    }

    /**
     * Set an error message
     *
     * @param string $error_message The error message
     * @return void
     */
    protected function setError($error_message)
    {
        $this->_errors = array_merge($this->_errors, array($error_message));
    }

    /**
     * Get errors
     *
     * @return array An array of error messages that have been set
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Log message to file
     * 
     * @param mixed $message Message to log
     * @param string $prepend Label message to prepend
     * @return void
     */
    public function log($message, $prepend = '')
    {
        if (!$this->_cfg['log']) {
            return false;
        }

        if (trim($prepend) != '') {
            $prepend .= "::";
        }

        $content = date('M d Y H:i:s') . ' [' . getmypid() . "]: "
            . $prepend . trim($message) . "\n";

        file_put_contents($this->_cfg['log_file'], $content, FILE_APPEND);
    }

    /**
     * Log a PDO Error
     * 
     * @param array $err PDO Error Info array
     * @return void
     */
    protected function _logPdoError($err)
    {
        // Log the error
        $this->log(
            $err[self::ERRINFO_ERROR_MESSAGE],
            'ERROR ' . $err[self::ERRINFO_SQLSTATE_CODE]
        );

        // Add to the Db Object error list
        $errorMessage = $err[self::ERRINFO_SQLSTATE_CODE]
            . ": " . $err[self::ERRINFO_ERROR_MESSAGE];

        $this->setError($errorMessage);
        throw new Exception(
            $errorMessage, $err[self::ERRINFO_ERROR_CODE]
        );
    }
}
