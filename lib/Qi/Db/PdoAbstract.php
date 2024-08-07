<?php

/**
 * Abstract Pdo Db class file
 *
 * @package Qi\Db
 */

namespace Qi\Db;

/**
 * Qi Db Abstract class
 *
 * Provides common functions for an interface to a PDO DB connection.
 *
 * @package Qi\Db
 * @mixin \PDOStatement
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.3.0
 */
class PdoAbstract
{
    /**#@+
     * Constants for Errinfo()
     *
     * @var int
     */
    public const ERRINFO_SQLSTATE_CODE = 0;
    public const ERRINFO_ERROR_CODE    = 1;
    public const ERRINFO_ERROR_MESSAGE = 2;
    /**#@-*/

    /**
     * Db Config settings
     *
     * @var array<string, string|int|bool>
     */
    protected $config;

    /**
     * Db Config defaults
     *
     * @var array<string, string|int|bool>
     */
    protected $configDefaults = [
        'log'      => false,
        'log_file' => '',
    ];

    /**
     * The database resource object
     *
     * @var \PDO
     */
    protected $resource;

    /**
     * Array of errors encountered
     *
     * @var string[]
     */
    protected $errors = [];

    /**
     * Character to delimit table names
     *
     * @var string
     */
    protected $tableDelimiterChar = '';

    /**
     * Constructor
     *
     * @param array<string,bool|int|string> $config Database configuration data
     * @return void
     */
    public function __construct($config = null)
    {
        if (!$config) {
            $config = [];
        }

        $this->config = array_merge($this->configDefaults, (array) $config);

        $this->init();
    }

    /**
     * Initialize DB resource
     *
     * Called right after constructor
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Safely execute a SQL query statement
     *
     * @param string $sqlString The sql query statement
     * @param array<string|int>|null $values Data to bind to query
     * @return \PDOStatement
     */
    public function executeQuery($sqlString, $values = null)
    {
        // Log the SQL statement if logging is enabled
        $this->log($sqlString);
        if (!empty($values)) {
            $this->log(trim(print_r($values, true)), 'DATA');
        }

        // Prepare the query
        $statement = $this->resource->prepare($sqlString);

        if (!$statement) {
            $exception = $this->logPdoError($this->resource->errorInfo());
            throw $exception;
        }

        // Execute the query
        if (!empty($values)) {
            $status = $statement->execute($values);
        } else {
            $status = $statement->execute();
        }

        if (!$status) {
            $exception = $this->logPdoError($statement->errorInfo());
            throw $exception;
        }

        return $statement;
    }

    /**
     * Insert a row into a table
     *
     * @param string $tableName Name of table
     * @param string[] $data Associative array with data to insert
     * @return string|false The resulting insert id or false
     */
    public function insert($tableName, $data)
    {
        $keys         = array_keys($data);
        $placeholders = array_fill(0, count($keys), "?");

        $sqlString = "(" . implode(',', $keys) . ") "
            . "VALUES (" . implode(',', $placeholders) . ")";

        $values = array_values($data);

        $this->rawInsert($tableName, $sqlString, $values);

        return $this->resource->lastInsertId();
    }

    /**
     * Safely insert rows into a table
     *
     * @param string $tableName The table name
     * @param string $sqlString The set part of the query e.g. "VALUES (...)"
     * @param string[]|null $values Optional data to bind to prepared statement
     * @return bool Whether the statement executed successfully
     */
    public function rawInsert($tableName, $sqlString, $values = null)
    {
        $tableName = $this->tableDelimiterChar . $tableName . $this->tableDelimiterChar;
        $sql = "INSERT INTO $tableName $sqlString";

        $this->executeQuery($sql, $values);

        return true;
    }

    /**
     * Update a row
     *
     * @param string $tableName Table name
     * @param string[] $data Associative array of data
     * @param string $where Where clause content
     * @param array<string|int> $whereValues The values to bind to the where clause
     * @return mixed
     */
    public function update($tableName, $data, $where, $whereValues = [])
    {
        $assignmentString = [];

        foreach ($data as $name => $value) {
            $assignmentString[] = "$name=?";
        }

        $assignmentString = implode(',', $assignmentString);

        $values = array_merge(array_values($data), $whereValues);

        return $this->rawUpdate($tableName, $assignmentString, $where, $values);
    }

    /**
     * Safely update row or rows in a table
     *
     * @param string $tableName The table name
     * @param string $assignmentString The set part of the query e.g. "col='value'"
     * @param string $where The where clause
     * @param array<string|int>|null $values Optional data to bind to prepared statement
     * @return bool Whether the statement executed successfully
     */
    public function rawUpdate($tableName, $assignmentString, $where, $values = null)
    {
        $tableName = $this->tableDelimiterChar . $tableName . $this->tableDelimiterChar;
        $sql = "UPDATE $tableName SET $assignmentString WHERE $where";

        $this->executeQuery($sql, $values);

        return true;
    }

    /**
     * delete
     *
     * @param string $tableName Name of table
     * @param string $where Where clause
     * @param array<string|int>|null $values Values to replace
     * @return bool Whether the statement executed successfully
     */
    public function delete($tableName, $where, $values = null)
    {
        return $this->rawDelete($tableName, $where, $values);
    }

    /**
     * Safely delete a row or rows from a table
     *
     * @param string $tableName The table name
     * @param string $where The where clause
     * @param array<string|int>|null $values Optional values to bind to prepared statement
     * @return bool Whether the statement executed successfully
     */
    public function rawDelete($tableName, $where, $values = null)
    {
        $tableName = $this->tableDelimiterChar . $tableName . $this->tableDelimiterChar;
        $sql = "DELETE FROM $tableName WHERE $where";

        $this->executeQuery($sql, $values);

        return true;
    }

    /**
     * Safely get a row from a table
     *
     * @param string $columns Comma separated list of columns to return
     * @param string $tableName The table name
     * @param string $where The where clause
     * @return array<string>|bool The row or an empty array
     */
    public function simpleFetchRow($columns, $tableName, $where)
    {
        if (trim($where) == '') {
            $where = 'true';
        }

        $sql = "SELECT $columns FROM $tableName WHERE $where LIMIT 1;";

        if ($rowData = $this->fetchRow($sql)) {
            return $rowData;
        }

        return [];
    }

    /**
     * Safely get rows from a table
     *
     * @param string $columns The columns to return
     * @param string $table The table name
     * @param string $where The where clause
     * @return array<string>|bool The rows or an empty array
     */
    public function simpleFetchRows($columns, $table, $where)
    {
        if (trim($where) == '') {
            $where = 'true';
        }

        $sql = "SELECT $columns FROM $table WHERE $where";

        if ($rowData = $this->fetchRows($sql)) {
            return $rowData;
        }

        return [];
    }

    /**
     * Execute a sql query and return the first resulting row
     *
     * @param string $query The sql query statement
     * @param string[]|null $values Data to bind to query
     * @param int $indices The array indices returned
     *                     (SQLITE_NUM, SQLITE_ASSOC, SQLITE_BOTH)
     * @return string[]|bool The resulting row or null
     */
    public function fetchRow($query, $values = null, $indices = \PDO::FETCH_ASSOC)
    {
        $statement = $this->executeQuery($query, $values);

        return $statement->fetch($indices);
    }

    /**
     * Execute a sql query and return the resulting rows
     *
     * @param string $query The sql query statement
     * @param string[]|null $values Data to bind to query
     * @param int $indices The array indices returned
     *                     (SQLITE_NUM, SQLITE_ASSOC, SQLITE_BOTH)
     * @return array<string>|false The resulting rows or false
     */
    public function fetchRows($query, $values = null, $indices = \PDO::FETCH_ASSOC)
    {
        $statement = $this->executeQuery($query, $values);

        return $statement->fetchAll($indices);
    }

    /**
     * Get a count of rows meeting a criteria
     *
     * @param string $tableName The table name
     * @param string $where The where clause
     * @return int|bool The resulting number of rows
     */
    public function getCount($tableName, $where)
    {
        if (trim($where) == '') {
            $where = 'true';
        }

        return $this->simpleFetchValue('count(*)', $tableName, $where);
    }

    /**
     * Simple method to fetch a single value from a row
     *
     * E.g. "SELECT email FROM users WHERE id=1;"
     *
     * @param string $column The column name to extract
     * @param string $tableName The table name
     * @param string $where The where clause
     * @return int|bool The data or false
     */
    public function simpleFetchValue($column, $tableName, $where)
    {
        $tableName = $this->tableDelimiterChar . $tableName . $this->tableDelimiterChar;
        $sql = "SELECT $column FROM $tableName WHERE $where";

        $statement = $this->executeQuery($sql);

        $row = $statement->fetch(\PDO::FETCH_NUM);

        if ($row == false) {
            return false;
        }

        if (isset($row[0])) {
            return $row[0];
        }

        return false;
    }

    /**
     * Safely alter a table
     *
     * @param string $tableName The table name
     * @param string $alter The alter part of statement e.g. "ADD COLUMN ... "
     * @return bool Whether the statement executed successfully
     */
    public function rawAlter($tableName, $alter)
    {
        $sql = "ALTER TABLE $tableName $alter";

        $this->executeQuery($sql);

        return true;
    }

    /**
     * Safely optimize a table
     *
     * @param string $table The table name
     * @return bool Whether the statement executed successfully
     */
    public function rawOptimize($table)
    {
        return false;
    }

    /**
     * Safely repair a table
     *
     * @param string $table The table name
     * @return bool Whether the statement executed successfully
     */
    public function rawRepair($table)
    {
        return false;
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
        return str_replace("'", "''", $string);
    }

    /**
     * Magic call method to pass down to db object
     *
     * @param string $method Method name
     * @param string[] $args Arguments
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->resource, $method], $args);
    }

    /**
     * Add an error message
     *
     * @param string $errorMessage The error message
     * @return object Self (fluid interface)
     */
    public function addError($errorMessage)
    {
        $this->errors = array_merge($this->errors, [$errorMessage]);

        return $this;
    }

    /**
     * Get errors
     *
     * @return string[] An array of error messages that have been set
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Log message to file
     *
     * @param mixed $message Message to log
     * @param string $label Label
     * @return bool
     */
    public function log($message, $label = null)
    {
        if (!$this->config['log']) {
            return false;
        }

        if (null === $label) {
            $label = date('Y-m-d H:i:s') . ' ' . getmypid();
        }

        file_put_contents(
            (string) $this->config['log_file'],
            $label . " ==> " . $message . "\n",
            FILE_APPEND
        );

        return true;
    }

    /**
     * Log a PDO Error
     *
     * @param array<string|int> $err PDO Error Info array
     * @return PdoException
     */
    protected function logPdoError($err)
    {
        // Log the error
        $this->log(
            $err[self::ERRINFO_ERROR_MESSAGE],
            'ERROR ' . $err[self::ERRINFO_SQLSTATE_CODE]
        );

        $errorMessage = $err[self::ERRINFO_SQLSTATE_CODE]
            . ": " . $err[self::ERRINFO_ERROR_MESSAGE];

        // Add to the Db Object error list
        $this->addError($errorMessage);

        return new PdoException(
            $errorMessage,
            (int) $err[self::ERRINFO_ERROR_CODE]
        );
    }
}
