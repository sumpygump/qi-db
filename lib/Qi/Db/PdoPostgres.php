<?php

/**
 * Pdo Postgres class file
 *
 * @package Qi\Db
 */

namespace Qi\Db;

/**
 * Qi Db Postgres class
 *
 * Provides common functions for interface to postgresql db.
 *
 * @package Qi\Db
 * @uses \Qi\Db\PdoAbstract
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.2.1
 */
class PdoPostgres extends PdoAbstract
{
    /**
     * Db Config defaults
     *
     * @var array<string, string|int|bool>
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
    protected $tableDelimiterChar = '';

    /**
     * Initialize DB resource
     *
     * Called right after constructor
     *
     * @return void
     */
    public function init()
    {
        if (trim((string) $this->config['db']) == '') {
            throw new PdoException("Invalid connection parameters.");
        }

        try {
            $this->resource = new \PDO(
                'pgsql:host=' . $this->config['host']
                . ';dbname=' . $this->config['db'],
                (string) $this->config['user'],
                (string) $this->config['pass']
            );
        } catch (\Exception $exception) {
            throw new PdoException($exception->getMessage());
        }

        if (!$this->resource) {
            throw new PdoException("PdoPostgres connection error.");
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
        return false;
    }

    /**
     * Safely repair a table
     *
     * @param string $tableName The table name
     * @return bool Whether the statement executed successfully
     */
    public function rawRepair($tableName)
    {
        return false;
    }
}
