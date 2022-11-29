<?php

/**
 * Qi_Db_PdoMysql Test class file
 *
 * @package Qi
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qi\Db\Tests;

use Qi\Db\PdoException;
use Qi\Db\PdoMysql;

/**
 * Qi_Console_PdoMysql Test class
 *
 * @package Qi
 * @author  Jansen Price <jansen.price@gmail.com>
 */
class PdoMysqlTest extends BaseTestCase
{
    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        $cfg = [
            'log'      => true,
            'log_file' => 'testdb.log',
            'host'     => 'localhost',
            'db'       => 'test1',
            'user'     => 'root',
            'pass'     => '',
        ];

        $this->createObject($cfg);
        $this->createTestTable();
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->dropTestTable();
        //@unlink('testdb.log');
    }

    /**
     * Create object
     *
     * @param  array $cfg Config
     * @return void
     */
    protected function createObject($cfg)
    {
        $this->_object = new PdoMysql($cfg);
    }

    /**
     * Constructor test with empty array
     *
     * @return void
     */
    public function testConstructEmptyArgs()
    {
        $this->expectException(PdoException::class);
        $this->expectExceptionMessage('Invalid');

        $cfg = [];

        $this->createObject($cfg);
    }

    /**
     * testConstructWithBadParams
     *
     * @return void
     */
    public function testConstructWithBadParams()
    {
        $this->expectException(PdoException::class);

        $cfg = [
            'dbfile' => ':mysql:dbname=testdb;unix_socket=/path/to/socket',
        ];

        $this->createObject($cfg);
    }

    /**
     * Test create table
     *
     * @return void
     */
    public function testCreateTable()
    {
        $sql = "select * from users";

        $r = $this->_object->fetchRows($sql);

        $this->assertEquals([], $r);
    }

    /**
     * Create table once it was already called
     *
     * @return            void
     */
    public function testCreateTableTwice()
    {
        $this->expectException('PdoException');
        $this->expectExceptionMessage('already');

        $this->createTestTable();
    }

    /**
     * Test safe query with data binding
     *
     * @return void
     */
    public function testRawQueryWithDataBinding()
    {
        $sql = "insert into users (name, email) values (?, ?)";

        $data = ['jansen', 'jansen@test.com'];

        $expected = [
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        $r = $this->_object->executeQuery($sql, $data);

        $sql = "select * from users";

        $actual = $this->_object->fetchRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * testInvalidQuery
     *
     * @return void
     */
    public function testInvalidQuery()
    {
        $this->expectException('PdoException');
        $this->expectExceptionMessage('doesn\'t');

        $sql = "SELECT * FROM foobar WHERE email=?";

        $data = ['test'];

        $result = $this->_object->executeQuery($sql, $data);
    }

    /**
     * Test raw insert
     *
     * @return void
     */
    public function testRawInsert()
    {
        $set = "(`name`, `email`) values ('jansen', 'jansen@test.com')";

        $expected = [
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        $r = $this->_object->rawInsert('users', $set);

        $sql = "select * from users";

        $actual = $this->_object->fetchRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * Test raw insert with error
     *
     * @return void
     */
    public function testRawInsertWithError()
    {
        $this->expectException('PdoException');
        $this->expectExceptionMessage('syntax');

        $set = "'name', 'email') values ('jansen', 'jansen@test.com')";

        $expected = false;

        $r = $this->_object->rawInsert('users', $set);
        $this->assertFalse($r);

        $sql = "select * from users";

        $actual = $this->_object->fetchRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * Test insert
     *
     * @return void
     */
    public function testInsert()
    {
        $data = [
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        $expected = [
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        $r = $this->_object->insert('users', $data);
        $this->assertEquals('1', $r);

        $sql = "select * from users";

        $actual = $this->_object->fetchRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * Test calling insert with a column that doesn't exist on table
     *
     * @return void
     */
    public function testInsertWithExtraColumn()
    {
        $this->expectException('PdoException');
        $this->expectExceptionMessage('flaxx');

        $data = [
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
            'flaxx' => 'none',
        ];

        $r = $this->_object->insert('users', $data);
        $this->assertFalse($r);
    }

    /**
     * Test safe update
     *
     * @return void
     */
    public function testSafeUpdate()
    {
        $response = $this->_object->rawUpdate(
            'users',
            "name='orihah'",
            'id=?',
            [1]
        );

        $this->assertTrue($response);
    }

    /**
     * Test update
     *
     * @return void
     */
    public function testUpdate()
    {
        $data = [
            'name' => 'orihah',
            'email' => 'orihah@test.com',
        ];

        $response = $this->_object->update('users', $data, 'id=?', [1]);
        $this->assertTrue($response);
    }

    public function testDelete()
    {
        $response = $this->_object->delete('users', 'id=?', [1]);
        $this->assertTrue($response);
    }

    /**
     * Test safe delete
     *
     * @return void
     */
    public function testRawDelete()
    {
        $response = $this->_object->rawDelete('users', 'id=?', [1]);
        $this->assertTrue($response);
    }

    /**
     * Test safe field
     *
     * @return void
     */
    public function testSimpleFetchValue()
    {
        $this->populateTestData();
        $name = $this->_object->simpleFetchValue('name', 'users', "id='1'");

        $this->assertEquals('jansen', $name);
    }

    /**
     * Test safe field without quotes
     *
     * @return void
     */
    public function testSimpleFetchValueWithoutQuotes()
    {
        $this->populateTestData();
        $name = $this->_object->simpleFetchValue('name', 'users', "id=1");

        $this->assertEquals('jansen', $name);
    }

    /**
     * Test safe field multiple columns
     *
     * @return void
     */
    public function testSimpleFetchValueMultipleColumns()
    {
        $this->populateTestData();
        $result = $this->_object->simpleFetchValue('name,email', 'users', "id=1");

        $this->assertEquals('jansen', $result);
    }

    /**
     * Test safe field no results
     *
     * @return void
     */
    public function testSimpleFetchValueNoResults()
    {
        $this->populateTestData();
        $result = $this->_object->simpleFetchValue('name', 'users', "id=22");

        $this->assertFalse($result);
    }

    /**
     * Test safe row
     *
     * @return void
     */
    public function testSimpleFetchRow()
    {
        $this->populateTestData();

        $result = $this->_object->simpleFetchRow('name,email', 'users', 'id=1');

        $expected = [
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe row no results
     *
     * @return void
     */
    public function testSimpleFetchRowNoResults()
    {
        $this->populateTestData();

        $result = $this->_object->simpleFetchRow('name,email', 'users', 'id=22');

        $this->assertEquals([], $result);
    }

    /**
     * Test safe row only one
     *
     * @return void
     */
    public function testSimpleFetchRowOnlyOne()
    {
        $this->populateTestData();
        $this->populateMoreTestData();

        $result = $this->_object->simpleFetchRow('name,email', 'users', '');

        $expected = [
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe rows
     *
     * @return void
     */
    public function testSimpleFetchRows()
    {
        $this->populateTestData();
        $this->populateMoreTestData();

        $result = $this->_object->simpleFetchRows('name,email', 'users', '');

        $expected = [
            [
                'name' => 'jansen',
                'email' => 'jansen@test.com',
            ],
            [
                'name' => 'orihah',
                'email' => 'orihah@test.com',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe rows no records
     *
     * @return void
     */
    public function testSimpleFetchRowsNoRecords()
    {
        $this->populateTestData();
        $this->populateMoreTestData();

        $result = $this->_object->simpleFetchRows('name,email', 'users', 'id > 22');

        $expected = [];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test get count
     *
     * @return void
     */
    public function testGetCount()
    {
        $this->populateTestData();
        $this->populateMoreTestData();

        $result = $this->_object->getCount('users', '');

        $this->assertEquals(2, $result);
    }

    /**
     * Test get count with where clause
     *
     * @return void
     */
    public function testGetCountWhereClause()
    {
        $this->populateTestData();
        $this->populateMoreTestData();

        $result = $this->_object->getCount('users', 'id=1');

        $this->assertEquals(1, $result);
    }

    /**
     * Test get count with no results
     *
     * @return void
     */
    public function testGetCountZero()
    {
        $this->populateTestData();
        $this->populateMoreTestData();

        $result = $this->_object->getCount('users', 'id > 101');

        $this->assertEquals(0, $result);
    }

    /**
     * Test raw alter
     *
     * @return void
     */
    public function testRawAlter()
    {
        $alter = 'ADD COLUMN active int(11)';

        $result = $this->_object->rawAlter('users', $alter);

        $statement = $this->_object->executeQuery("describe `users`");

        $schema = $statement->fetchAll(\PDO::FETCH_ASSOC);

        // This is the expected third column (row in pragma array)
        $expected = [
            'Field'      => 'active',
            'Type'       => 'int',
            'Null'       => 'YES',
            'Key'        => '',
            'Default'    => null,
            'Extra'      => '',
        ];

        $this->assertEquals($expected, $schema[3]);
    }

    /**
     * Test raw alter with an error
     *
     * @return void
     */
    public function testRawAlterError()
    {
        $this->expectException('PdoException');
        $this->expectExceptionMessage('Multiple');

        $alter = 'ADD COLUMN active integer PRIMARY KEY';

        $result = $this->_object->rawAlter('users', $alter);
    }

    /**
     * Test safe optimize
     *
     * @return void
     */
    public function testRawOptimize()
    {
        $result = $this->_object->rawOptimize('users');

        $this->assertTrue($result);
    }

    /**
     * Test safe repair
     *
     * @return void
     */
    public function testRawRepair()
    {
        $result = $this->_object->rawRepair('users');

        $this->assertTrue($result);
    }

    /**
     * Test get row no results
     *
     * @return void
     */
    public function testFetchRowNoResults()
    {
        $this->populateTestData();
        $result = $this->_object->fetchRow('SELECT * FROM users WHERE id=22');

        $this->assertFalse($result);
    }

    /**
     * Test get row with bind data
     *
     * @return void
     */
    public function testFetchRowWithBindData()
    {
        $data = [
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        $expected = [
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        $r = $this->_object->insert('users', $data);

        $q = "select * from users where id=?";

        $data = ['1'];

        $r = $this->_object->fetchRow($q, $data);
        $this->assertEquals($expected, $r);
    }

    /**
     * Test escape
     *
     * @return void
     */
    public function testEscape()
    {
        $result = $this->_object->escape("Don't blink");

        $expected = "Don''t blink";

        $this->assertEquals($expected, $result);
    }

    /**
     * Test magic call
     *
     * @return void
     */
    public function testMagicCall()
    {
        $result = $this->_object->errorInfo();

        $this->assertEquals(3, count($result));
    }

    /**
     * Test set error
     *
     * @return void
     */
    public function testAddError()
    {
        $this->_object->addError('');

        $result = $this->_object->getErrors();

        $this->assertEquals([''], $result);
    }

    /**
     * Test the log method
     *
     * @return void
     */
    public function testLog()
    {
        $cfg = [
            'db'       => 'test1',
            'user'     => 'root',
            'pass'     => '',
            'log'      => false,
            'log_file' => 'testdb.log',
        ];

        $this->createObject($cfg);

        $result = $this->_object->log('a message');

        $this->assertFalse($result);
    }

    /**
     * Create test table
     *
     * @return void
     */
    protected function createTestTable()
    {
        $sql = "create table users (
            `id` tinyint auto_increment,
            `name` varchar(50),
            `email` varchar(50),
            primary key (`id`)
        );";

        $this->_object->executeQuery($sql);
    }

    protected function dropTestTable()
    {
        $sql = "DROP TABLE `users`";

        $this->_object->executeQuery($sql);
    }

    /**
     * Populate test data
     *
     * @return void
     */
    protected function populateTestData()
    {
        $data = [
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        return $this->_object->insert('users', $data);
    }

    /**
     * Populate more test data
     *
     * @return void
     */
    protected function populateMoreTestData()
    {
        $data = [
            'name'  => 'orihah',
            'email' => 'orihah@test.com',
        ];

        return $this->_object->insert('users', $data);
    }
}
