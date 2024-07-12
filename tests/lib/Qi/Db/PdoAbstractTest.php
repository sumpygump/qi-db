<?php

/**
 * Qi_Db_PdoAbstract Test class file
 *
 * @package Qi\Db\Tests
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qi\Db\Tests;

use PHPUnit\Framework\TestCase;
use Qi\Db\PdoAbstract;

class QiPdoFoo extends PdoAbstract
{
    /**
     * Get the config settings
     *
     * @return array<string, bool|int|string>
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Publicly exposed version of logPdoError
     *
     * @param array<string|int> $err
     * @return \Qi\Db\PdoException
     */
    public function publicLogPdoError($err)
    {
        return $this->logPdoError($err);
    }
}

/**
 * PdoAbstract test class
 *
 * This test involves creating a dummy class that extends the PdoAbstract class
 * that does nothing, and tests the common methods of the abstract class via
 * the dummy class child.
 *
 * It does not actually connect to a database.
 *
 * @package Qi\Db\Tests
 * @author  Jansen Price <jansen.price@gmail.com>
 */
class PdoAbstractTest extends TestCase
{
    /**
     * The object under test
     *
     * @var QiPdoFoo
     */
    public $object;

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

    /**
     * @return void
     */
    public function testRawOptimize()
    {
        $result = $this->object->rawOptimize('table1');
        $this->assertEquals(null, $result);
    }

    /**
     * @return void
     */
    public function testRawRepair()
    {
        $result = $this->object->rawRepair('table1');
        $this->assertEquals(null, $result);
    }

    /**
     * @return void
     */
    public function testLogPdoError()
    {
        $result = $this->object->publicLogPdoError(['code1', 2, 'message3']);
        $expected = ['code1: message3'];
        $this->assertEquals($expected, $this->object->getErrors());
        $this->assertEquals(\Qi\Db\PdoException::class, $result::class);
    }
}
