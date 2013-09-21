<?php
/**
 * Postgresql database file
 *
 * @package Qi
 * @subpackage Db
 */

/** Default timezone */
date_default_timezone_set('America/Chicago');

/**
 * Qi_Db_Postgresql
 *
 * Provides common functions for an interface to postgresql db.
 *
 * @package Qi
 * @subpackage Db
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 0.8
 */
class Qi_Db_Postgresql
{
    /**
     * @var string The postgresql host to connect to
     */
    protected $host;

    /**
     * @var string The name of the database
     */
    protected $db;

    /**
     * @var string The database user to login with
     */
    protected $user;

    /**
     * @var string The database user's password
     */
    protected $pass;

    /**
     * @var object Resource The database resource object
     */
    protected $link;

    /**
     * @var array Logging configuration settings
     */
    protected $q_log;

    /**
     * @var bool Debug mode
     */
    protected $_debug_mode = false;

    /**
     * Constructor
     *
     * @param array $dbcfg Array with configuration details
     * @return void
     */
    public function __construct($dbcfg)
    {
        $this->q_log['log'] = isset($dbcfg['log']) ? $dbcfg['log'] : false;

        $this->q_log['log_file'] =
            isset($dbcfg['log_file']) ? $dbcfg['log_file'] : '';

        $this->host = $dbcfg['host'];
        $this->db   = $dbcfg['db'];
        $this->user = $dbcfg['user'];
        $this->pass = $dbcfg['pass'];

        $pg_connection_string  = "host='" . $this->host . "'";
        $pg_connection_string .= " port='5432'";
        $pg_connection_string .= " dbname='" . $this->db . "'";
        $pg_connection_string .= " user='" . $this->user . "'";
        $pg_connection_string .= " password='" . $this->pass . "'";

        $this->link = pg_connect($pg_connection_string);
        if (!$this->link) {
            die("PostgreSql connection error.");
        }
    }

    /**
     * __destruct
     *
     * @return void
     */
    public function __destruct()
    {
        if (is_object($this->link)) {
            pg_close($this->link);
        }
    }

    /**
     * Sanitize a string for sql statement
     *
     * @param string $string The string to sanitize
     * @return string
     */
    public function escape_string($string)
    {
        return pg_escape_string($this->link, $string);
    }

    /**
     * Safely run a sql query
     *
     * @param string $q The sql statement
     * @return array The resulting rows
     */
    public function safe_query($q='')
    {
        $method = "pg_query";
        if (!$q) {
            return false;
        }

        // Log the sql statement
        if ($this->q_log['log']) {
            file_put_contents(
                $this->q_log['log_file'],
                date("m/d/Y H:i:s") . " ==>\n" . $q ."\n", FILE_APPEND
            );
        }

        // Execute the query
        //  If the query fails, it will generate an error.
        //  To detect this we have to set an error_handler
        //  and re-throw a caught exception, so that we can log it too.
        set_error_handler(array(__CLASS__, "handle_error"));
        try {
            $result = $method($this->link, $q);
        } catch (Exception $e) {
            restore_error_handler();
            if ($this->q_log['log']) {
                file_put_contents(
                    $this->q_log['log_file'],
                    "Error ==> " . $e->getMessage() . "\n\n", FILE_APPEND
                );
            }
            throw new Qi_Db_PostgresqlException($e->getMessage(), $e->getCode());
        }
        restore_error_handler();

        // Log the result or an error if any
        if ($this->q_log['log']) {
            $handle = fopen($this->q_log['log_file'], 'a');
            if (!$result) {
                fwrite($handle, "Error  ==> ".pg_last_error()."\n\n");
            } else {
                fwrite(
                    $handle, "Result ==> " . pg_num_rows($result)
                    . " row(s)\n\n"
                );
            }
            fclose($handle);
        }

        if (!$result) {
            return false;
        }
        return $result;
    }

    /**
     * Capture an error and throw it as a Db_PostgresqlException
     *
     * @return void
     */
    public static function handle_error()
    {
        list($errno, $message, $file, $line) = func_get_args();

        $message = $message . " in " . $file . ":" . $line . ".";
        throw new Qi_Db_PostgresqlException($message, $errno);
    }

    /**
     * Safely extract a column from a row
     *
     * @param string $thing The thing to extract
     * @param string $table The table name
     * @param string $where The where clause
     * @return array|bool The resulting row or false
     */
    public function safe_field($thing, $table, $where)
    {
        $q = "select $thing from $table where $where";
        $r = $this->safe_query($q);
        if (pg_num_rows($r) > 0) {
            //pg_result_seek($r, 0);
            return pg_fetch_result($r, 0, 0);
        }
        return false;
    }

    /**
     * Safely extract column values from a row or rows
     *
     * @param string $thing The thing to extract
     * @param string $table the table name
     * @param string $where The where clause
     * @return string|array A comma separated list of the values
     *                      returned or an empty array
     */
    public function safe_column($thing, $table, $where)
    {
        $q  = "select $thing from $table where $where";
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
     * @param string $things Comma separated list of columns to return
     * @param string $table The table name
     * @param string $where The where clause
     * @return array The row or an empty array
     */
    public function safe_row($things, $table, $where)
    {
        $where = $this->_sanitize_where($where);
        $q     = "select $things from $table where $where";
        $rs    = $this->getRow($q);
        if ($rs) {
            return $rs;
        }
        return array();
    }

    /**
     * Safely get rows from a table
     *
     * @param string $things The columns to return
     * @param string $table The table name
     * @param string $where The where clause
     * @return array The rows or an empty array
     */
    public function safe_rows($things, $table, $where)
    {
        $where = $this->_sanitize_where($where);
        $q     = "select $things from $table where $where";
        $rs    = $this->getRows($q);
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
    public function safe_count($table, $where="TRUE")
    {
        $where = $this->_sanitize_where($where);
        return $this->getThing("select count(*) from $table where $where");
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
        $q = "select $col from $table where $key = '$val' limit 1;";
        $r = $this->safe_query($q);
        if ($r && pg_num_rows($r) > 0) {
            pg_result_seek($r, 0);
            return pg_fetch_result($r, 0, 0);
        }
        return false;
    }

    /**
     * Execute a sql query and return the first resulting row
     *
     * @param string $query The sql query statement
     * @param int $indices The array indices returned
     *                     (PGSQL_NUM, PGSQL_ASSOC, PGSQL_BOTH)
     * @return array|bool The resulting row or false
     */
    public function getRow($query, $indices=PGSQL_ASSOC)
    {
        $r = $this->safe_query($query);
        if ($r && pg_num_rows($r) > 0) {
            return pg_fetch_array($r, null, $indices);
        }
        return false;
    }

    /**
     * Execute a sql query and return the resulting rows
     *
     * @param string $query The sql query statement
     * @return array The resulting rows or false
     */
    public function getRows($query)
    {
        $r = $this->safe_query($query);
        if ($r && pg_num_rows($r) > 0) {
            return pg_fetch_all($r);
        }
        return false;
    }

    /**
     * Execute a sql query and return the first column in the resulting row
     *
     * @param string $query The sql query statement
     * @return mixed The resulting thing or false
     */
    public function getThing($query)
    {
        $r = $this->safe_query($query);
        if ($r) {
            return (pg_num_rows($r) != 0) ? pg_fetch_result($r, 0) : '';
        }
        return false;
    }

    /**
     * getThings
     * return values of one column from multiple rows in an num indexed array
     *
     * @param string $query The sql query statement
     * @return array The resulting rows
     */
    public function getThings($query)
    {
        $rs = $this->getRows($query);
        if ($rs) {
            foreach ($rs as $a) {
                $out[] = $a[0];
            }
            return $out;
        }
        return array();
    }

    /**
     * Get a count of rows meeting a criteria
     *
     * @param string $table The table name
     * @param string $where The where clause
     * @return string The resulting number of rows
     */
    public function getCount($table, $where="TRUE")
    {
        return $this->safe_count($table, $where);
    }

    /**
     * Sanitize the where clause
     *
     * With postgresql, you cannot use the query "select * from table where 1",
     *  it must be "select * from table where TRUE"
     *  this method corrects that
     *
     * @param string $where The where clause to sanitize
     * @return void
     */
    protected function _sanitize_where($where)
    {
        if ($where === '1' || $where == '') {
            $where = "TRUE";
        }
        return $where;
    }

    /**
     * Safely delete rows from a table
     *
     * @param string $table The name of the table
     * @param string $where The where clause
     * @return bool Whether the sql was successful
     */
    public function safe_delete($table, $where)
    {
        $q = "delete from $table where $where";
        $r = $this->safe_query($q);
        return (bool) $r;
    }

    /**
     * Safely update rows in a table
     *
     * @param string $table The table name
     * @param string $set The set part of the query "col='value'"
     * @param string $where The where clause
     * @return bool Whether the sql was successful
     */
    public function safe_update($table, $set, $where)
    {
        $q = "update $table set $set where $where";
        $r = $this->safe_query($q);
        return (bool) $r;
    }

    /**
     * Safely insert rows into a table
     *
     * @param string $table The table name
     * @param string $set The set part of the query "VALUES(...)"
     * @return bool Whether the sql was successful
     */
    public function safe_insert($table, $set)
    {
        $q = "insert into $table $set";
        $r = $this->safe_query($q);
        return (bool) $r;
    }

    /**
     * Safely run an alter statement
     *
     * @param string $table The table name
     * @param string $alter The alter part of statement e.g. "ADD COLUMN x ..."
     * @return bool Whether the sql was successful
     */
    public function safe_alter($table, $alter)
    {
        $q = "alter table $table $alter";
        $r = $this->safe_query($q);
        return (bool) $r;
    }
}

/**
 * Qi_Db_PostgresqlException
 *
 * @uses Exception
 * @package Qi
 * @subpackage Db
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version $Id$
 */
class Qi_Db_PostgresqlException extends Exception
{
}
