<?php
/**
 * PdoSqlite Db class file
 *
 * @package Qi
 * @subpackage Db
 */

/**
 * Qi Db PdoSqlite class
 *
 * Provides common functions for an interface to sqlite db.
 *
 * @package Qi
 * @subpackage Db
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.2.1
 */
class Qi_Db_PdoSqlite extends Qi_Db_PdoAbstract
{
    /**
     * Db Config defaults
     *
     * @var array
     */
    protected $_configDefaults = array(
        'log'      => false,
        'log_file' => '',
        'dbfile'   => 'data.db3',
        'version'  => '3',
    );

    /**
     * Character to delimit table names
     *
     * @var string
     */
    protected $_tableDelimiterChar = '';

    /**
     * Initialize DB resource
     *
     * Called right after constructor
     *
     * @return void
     */
    public function init()
    {
        if ($this->_config['version'] == '2') {
            $dsnPrefix = 'sqlite2';
        } else {
            $dsnPrefix = 'sqlite';
        }

        try {
            $this->_resource = new PDO($dsnPrefix . ':' . $this->_config['dbfile']);
        } catch (Exception $exception) {
            throw new Qi_Db_PdoException($exception->getMessage());
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
