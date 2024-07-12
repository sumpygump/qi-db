<?php

/**
 * PdoSqlite Db class file
 *
 * @package Qi\Db
 */

namespace Qi\Db;

/**
 * Qi Db PdoSqlite class
 *
 * Provides common functions for an interface to sqlite db.
 *
 * @package Qi\Db
 * @uses \Qi\Db\PdoAbstract
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.3.0
 */
class PdoSqlite extends PdoAbstract
{
    /**
     * Db Config defaults
     *
     * @var array<string, string|int|bool>
     */
    protected $configDefaults = [
        'log'      => false,
        'log_file' => '',
        'dbfile'   => 'data.db3',
        'version'  => '3',
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
        if ($this->config['version'] == '2') {
            $dsnPrefix = 'sqlite2';
        } else {
            $dsnPrefix = 'sqlite';
        }

        try {
            $this->resource = new \PDO($dsnPrefix . ':' . $this->config['dbfile']);
        } catch (\Exception $exception) {
            throw new PdoException($exception->getMessage());
        }
    }

    /**
     * Safely optimize a table
     *
     * @param string $table The table name
     * @return bool Whether the statement executed successfully
     */
    public function rawOptimize($table)
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
    public function rawRepair($table)
    {
        $this->log("Repair is not available for sqlite.", "Warning");

        return false;
    }
}
