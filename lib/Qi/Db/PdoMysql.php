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
 * Provides common functions for interface to mysql db.
 * 
 * @package Qi
 * @subpackage Db
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.2.1
 */
class Qi_Db_PdoMysql extends Qi_Db_PdoAbstract
{
    /**
     * Db Config defaults
     *
     * @var array
     */
    protected $_configDefaults = array(
        'log'      => false,
        'log_file' => '',
        'host'     => '',
        'db'       => '',
        'user'     => '',
        'pass'     => '',
    );

    /**
     * Character to delimit table names
     *
     * @var string
     */
    protected $_tableDelimiterChar = '`';

    /**
     * Initialize DB resource
     *
     * Called right after constructor
     *
     * @return void
     */
    public function init()
    {
        if (trim($this->_config['db']) == '') {
            throw new Qi_Db_PdoException("Invalid connection parameters.");
        }

        try {
            $this->_resource = new PDO(
                'mysql:host=' . $this->_config['host']
                . ';dbname=' . $this->_config['db'],
                $this->_config['user'],
                $this->_config['pass']
            );
        } catch (Exception $exception) {
            throw new Qi_Db_PdoException($exception->getMessage());
        }

        if (!$this->_resource) {
            throw new Qi_Db_PdoException("PdoMysql connection error.");
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
        $tableName = $this->_tableDelimiterChar . $tableName . $this->_tableDelimiterChar;
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
        $tableName = $this->_tableDelimiterChar . $tableName . $this->_tableDelimiterChar;
        $sql = "REPAIR TABLE $tableName";
       
        $this->executeQuery($sql);

        return true;
    }
}
