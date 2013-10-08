<?php
/**
 * Qi_Db_PdoAbstract Test class file
 *
 * @package Qi
 */

class Qi_Db_PdoFoo extends Qi_Db_PdoAbstract
{
}

/**
 * Qi_Console_PdoAbstract Test class
 *
 * @uses BaseTestCase
 * @package Qi
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qi_Db_PdoAbstractTest extends BaseTestCase
{
    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp()
    {
        $config = array(
            'db' => 'test1',
        );

        $this->_object = new Qi_Db_PdoFoo($config);
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown()
    {
    }

    /**
     * Test construct
     *
     * @expectedException PHPUnit_Framework_Error_Warning Missing
     * @return void
     */
    public function testConstruct()
    {
        $object = new Qi_Db_PdoFoo();
    }

    /**
     * testInit
     *
     * @return void
     */
    public function testInit()
    {
        $config = array(
            'db' => 'test1',
        );

        $object = new Qi_Db_PdoFoo($config);
    }

    public function testRawOptimize()
    {
        $this->_object->rawOptimize('table1');
    }

    public function testRawRepair()
    {
        $this->_object->rawRepair('table1');
    }
}
