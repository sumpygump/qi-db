<?php

/**
 * Qi\Db\PdoSqlite Test class file
 *
 * @package Qi\Tests
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qi\Db\Tests;

use PHPUnit\Framework\TestCase;
use Qi\Db\PdoSqlite;
use Qi\Db\PdoException;

/**
 * PdoSqlite Test class
 *
 * @package Qi\Tests
 * @author  Jansen Price <jansen.price@gmail.com>
 */
class PdoSqliteTest extends TestCase
{
    /**
     * The object under test
     *
     * @var PdoSqlite
     */
    public $object;

    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        $cfg = [
            'dbfile'   => 'test.db3',
            'log'      => true,
            'log_file' => 'testdb.log',
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
        @unlink('test.db3');
        @unlink('data.db3');
        @unlink('test.db2');
        @unlink('testdb.log');
    }

    /**
     * Constructor test with empty array
     *
     * @return void
     */
    public function testConstructEmptyArgs()
    {
        $cfg = [];

        $this->createObject($cfg);
        $this->assertTrue(is_object($this->object));
    }

    /**
     * TestConstructSqliteVersionTwo
     *
     * This fails because it can't find the driver for sqlite2
     *
     * @return void
     */
    public function testConstructSqliteVersionTwo()
    {
        $this->expectException(PdoException::class);

        $cfg = array(
            'dbfile'  => 'test.db2',
            'version' => '2',
        );

        $this->createObject($cfg);
    }

    /**
     * Test initializing connection to db file to a folder without write
     * permissions
     *
     * @return void
     */
    public function testConstructToFolderWithoutWritePerms()
    {
        $this->expectException(PdoException::class);

        $cfg = [
            'dbfile' => '/etc/testsqlite.db3',
        ];

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

        $r = $this->object->fetchRows($sql);

        $this->assertEquals([], $r);
    }

    /**
     * Create table once it was already called
     *
     * @return void
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

        $r = $this->object->executeQuery($sql, $data);

        $sql = "select * from users";

        $actual = $this->object->fetchRows($sql);

        if (false == $actual) {
            $actual = [];
        }
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
        $this->expectExceptionMessage('such');

        $sql = "SELECT * FROM foobar WHERE email=?";

        $data = [];

        $result = $this->object->executeQuery($sql, $data);
    }

    /**
     * Test raw insert
     *
     * @return void
     */
    public function testRawInsert()
    {
        $set = "('name', 'email') values ('jansen', 'jansen@test.com')";

        $expected = [
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        ];

        $r = $this->object->rawInsert('users', $set);

        $sql = "select * from users";

        $actual = $this->object->fetchRows($sql);

        if (false == $actual) {
            $actual = [];
        }
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

        $r = $this->object->rawInsert('users', $set);
        $this->assertFalse($r);

        $sql = "select * from users";

        $actual = $this->object->fetchRows($sql);

        if (false == $actual) {
            $actual = [];
        }
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

        $r = $this->object->insert('users', $data);
        $this->assertEquals('1', $r);

        $sql = "select * from users";

        $actual = $this->object->fetchRows($sql);

        if (false == $actual) {
            $actual = [];
        }
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

        $r = $this->object->insert('users', $data);
        $this->assertFalse($r);
    }

    /**
     * Test safe update
     *
     * @return void
     */
    public function testRawUpdate()
    {
        $response = $this->object->rawUpdate(
            'users',
            "name='orihah'",
            'id=?',
            [1],
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

        $response = $this->object->update('users', $data, 'id=?', [1]);
        $this->assertTrue($response);
    }

    /**
     * Test delete
     *
     * @return void
     */
    public function testDelete()
    {
        $response = $this->object->delete('users', 'id=?', [1]);
        $this->assertTrue($response);
    }

    /**
     * Test safe delete
     *
     * @return void
     */
    public function testRawDelete()
    {
        $response = $this->object->rawDelete('users', 'id=?', [1]);
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
        $name = $this->object->simpleFetchValue('name', 'users', "id='1'");

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
        $name = $this->object->simpleFetchValue('name', 'users', "id=1");

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
        $result = $this->object->simpleFetchValue('name,email', 'users', "id=1");

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
        $result = $this->object->simpleFetchValue('name', 'users', "id=22");

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

        $result = $this->object->simpleFetchRow('name,email', 'users', 'id=1');

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

        $result = $this->object->simpleFetchRow('name,email', 'users', 'id=22');

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

        $result = $this->object->simpleFetchRow('name,email', 'users', '');

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

        $result = $this->object->simpleFetchRows('name,email', 'users', '');

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

        $result = $this->object->simpleFetchRows('name,email', 'users', 'id > 22');

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

        $result = $this->object->getCount('users', '');

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

        $result = $this->object->getCount('users', 'id=1');

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

        $result = $this->object->getCount('users', 'id > 101');

        $this->assertEquals(0, $result);
    }

    /**
     * Test raw alter
     *
     * @return void
     */
    public function testRawAlter()
    {
        $alter = 'ADD COLUMN active integer';

        $result = $this->object->rawAlter('users', $alter);

        $statement = $this->object->executeQuery("PRAGMA table_info('users')");

        $pragma = $statement->fetchAll(\PDO::FETCH_ASSOC);

        // This is the expected third column (row in pragma array)
        $expected = [
            'cid'        => 3,
            'name'       => 'active',
            'type'       => 'INTEGER',
            'notnull'    => 0,
            'dflt_value' => null,
            'pk'         => 0,
        ];

        $this->assertEquals($expected, $pragma[3]);
    }

    /**
     * Test raw alter with an error
     *
     * @return void
     */
    public function testRawAlterError()
    {
        $this->expectException('PdoException');
        $this->expectExceptionMessage('Cannot');

        $alter = 'ADD COLUMN active integer PRIMARY KEY';

        $result = $this->object->rawAlter('users', $alter);
    }

    /**
     * Test safe optimize
     *
     * @return void
     */
    public function testRawOptimize()
    {
        $result = $this->object->rawOptimize('users');

        $this->assertFalse($result);
    }

    /**
     * Test safe repair
     *
     * @return void
     */
    public function testRawRepair()
    {
        $result = $this->object->rawRepair('users');

        $this->assertFalse($result);
    }

    /**
     * Test get row no results
     *
     * @return void
     */
    public function testFetchRowNoResults()
    {
        $this->populateTestData();
        $result = $this->object->fetchRow('SELECT * FROM users WHERE id=22');

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

        $r = $this->object->insert('users', $data);

        $q = "select * from users where id=?";

        $data = ['1'];

        $r = $this->object->fetchRow($q, $data);
        $this->assertEquals($expected, $r);
    }

    /**
     * Test escape
     *
     * @return void
     */
    public function testEscape()
    {
        $result = $this->object->escape("Don't blink");

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
        $result = $this->object->errorInfo();

        $this->assertEquals(3, count($result));
    }

    /**
     * Test set error
     *
     * @return void
     */
    public function testAddError()
    {
        $this->object->addError('');

        $result = $this->object->getErrors();

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
            'dbfile'   => 'test.db3',
            'log'      => false,
            'log_file' => 'testdb.log',
        ];

        $this->createObject($cfg);

        $result = $this->object->log('a message');

        $this->assertFalse($result);
    }

    /**
     * Create object
     *
     * @param array<string, bool|int|string> $cfg Config
     * @return void
     */
    protected function createObject($cfg)
    {
        $this->object = new PdoSqlite($cfg);
    }

    /**
     * Create test table
     *
     * @return void
     */
    protected function createTestTable()
    {
        $sql = "create table users (
            'id' integer primary key,
            'name' text,
            'email' text
        );";

        $this->object->executeQuery($sql);
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

        $this->object->insert('users', $data);
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

        $this->object->insert('users', $data);
    }
}
