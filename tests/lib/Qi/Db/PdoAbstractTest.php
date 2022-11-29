<?php

/**
 * Qi_Db_PdoAbstract Test class file
 *
 * @package Qi
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qi\Db\Tests;

use Qi\Db\PdoAbstract;

class QiPdoFoo extends PdoAbstract
{
    public function getConfig()
    {
        return $this->config;
    }
}

/**
 * Qi_Console_PdoAbstract Test class
 *
 * @package Qi
 * @author  Jansen Price <jansen.price@gmail.com>
 */
class PdoAbstractTest extends BaseTestCase
{
    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        $config = [
            'db' => 'test1',
        ];

        $this->object = new QiPdoFoo($config);
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    /**
     * Test construct
     *
     * @expectedException PHPUnit_Framework_Error_Warning Missing
     * @return            void
     */
    public function testConstruct()
    {
        $object = new QiPdoFoo();
        $this->assertEquals('', $object->getConfig()['log_file']);
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

        $object = new QiPdoFoo($config);
        $this->assertEquals('test1', $object->getConfig()['db']);
    }

    public function testRawOptimize()
    {
        $result = $this->object->rawOptimize('table1');
        $this->assertEquals(null, $result);
    }

    public function testRawRepair()
    {
        $result = $this->object->rawRepair('table1');
        $this->assertEquals(null, $result);
    }
}
