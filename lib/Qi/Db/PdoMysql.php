<?php

/**
 * Pdo Mysql class file
 *
 * @package Qi
 * @subpackage Db
 */

namespace Qi\Db;

/**
 * Qi Db PdoMysql class
 *
 * Provides common functions for interface to mysql db.
 *
 * @package Qi
 * @subpackage Db
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.2.1
 */
class PdoMysql extends PdoAbstract
{
    /**
     * Db Config defaults
     *
     * @var array
     */
    protected $configDefaults = [
        'log'      => false,
        'log_file' => '',
        'host'     => '',
        'db'       => '',
        'user'     => '',
        'pass'     => '',
    ];

    /**
     * Character to delimit table names
     *
     * @var string
     */
    protected $tableDelimiterChar = '`';

    /**
     * Initialize DB resource
     *
     * Called right after constructor
     *
     * @return void
     */
    public function init()
    {
        if (trim($this->config['db']) == '') {
            throw new PdoException("Invalid connection parameters.");
        }

        try {
            $this->resource = new \PDO(
                'mysql:host=' . $this->config['host']
                . ';dbname=' . $this->config['db'],
                $this->config['user'],
                $this->config['pass']
            );
        } catch (\Exception $exception) {
            throw new PdoException($exception->getMessage());
        }

        if (!$this->resource) {
            throw new PdoException("PdoMysql connection error.");
        }
    }

    /**
     * Safely optimize a table
     *
     * @param string $tableName The table name
     * @return bool Whether the statement executed successfully
     */
    public function rawOptimize($tableName)
    {
        $tableName = $this->tableDelimiterChar . $tableName . $this->tableDelimiterChar;
        $sql = "OPTIMIZE TABLE $tableName";

        $this->executeQuery($sql);

        return true;
    }

    /**
     * Safely repair a table
     *
     * @param string $tableName The table name
     * @return bool Whether the statement executed successfully
     */
    public function rawRepair($tableName)
    {
        $tableName = $this->tableDelimiterChar . $tableName . $this->tableDelimiterChar;
        $sql = "REPAIR TABLE $tableName";

        $this->executeQuery($sql);

        return true;
    }
}
