<?php

/**
 * Qi_Db_PdoPostgres Test class file
 *
 * @package Qi\Db\Tests
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qi\Db\Tests;

use PHPUnit\Framework\TestCase;
use Qi\Db\PdoException;
use Qi\Db\PdoPostgres;

/**
 * Qi_Console_PdoPostgres Test class
 *
 * @package Qi\Db\Tests
 * @author  Jansen Price <jansen.price@gmail.com>
 */
class PdoPostgresTest extends TestCase
{
    /**
     * The object under test
     *
     * @var PdoPostgres
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
            'host'     => POSTGRES_HOST,
            'user'     => POSTGRES_USER,
            'pass'     => POSTGRES_PASS,
            'db'       => POSTGRES_DB,
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
        $this->dropTestTable();
        //@unlink('testdb.log');
    }

    /**
     * Create object
     *
     * @param string[] $cfg Config
     * @return void
     */
    protected function createObject($cfg)
    {
        $this->object = new PdoPostgres($cfg);
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
        $this->expectExceptionMessage("could not translate host name");

        $cfg = [
            'host'     => 'picklejuice',
            'user'     => POSTGRES_USER,
            'pass'     => POSTGRES_PASS,
            'db'       => POSTGRES_DB,
            'log'      => true,
            'log_file' => 'testdb.log',
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
        $sql = "SELECT * FROM users";

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
     * Test raw query with data binding
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
        $this->expectExceptionMessage("does not exist");

        $sql = "SELECT * FROM foobar WHERE email=?";

        $data = ['test'];

        $result = $this->object->executeQuery($sql, $data);
    }

    /**
     * Test raw insert
     *
     * @return void
     */
    public function testRawInsert()
    {
        $set = "(name, email) values ('jansen', 'jansen@test.com')";

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
     * Test raw update
     *
     * @return void
     */
    public function testSafeUpdate()
    {
        $response = $this->object->rawUpdate(
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
     * Test raw delete
     *
     * @return void
     */
    public function testRawDelete()
    {
        $response = $this->object->rawDelete('users', 'id=?', [1]);
        $this->assertTrue($response);
    }

    /**
     * Test raw field
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
     * Test raw field without quotes
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
     * Test raw field multiple columns
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
     * Test raw field no results
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
     * Test raw row
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
     * Test raw row no results
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
     * Test raw row only one
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
     * Test raw rows
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
     * Test raw rows no records
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
        $alter = 'ADD COLUMN active int';

        $result = $this->object->rawAlter('users', $alter);

        // Try to use the new column to insert data
        $sql = "INSERT INTO users (name, email, active) values (?, ?, ?)";
        $data = ['jansen', 'jansen@test.com', '1'];
        $r = $this->object->executeQuery($sql, $data);

        // Check that it worked
        $sql = "SELECT * FROM users;";
        $users = $this->object->fetchRows($sql);

        $expected = [
            'id' => 1,
            'name' => 'jansen',
            'email' => 'jansen@test.com',
            'active' => 1,
        ];

        if (false == $users) {
            $users = [];
        }
        $this->assertEquals($expected, end($users));
    }

    /**
     * Test raw alter with an error
     *
     * @return void
     */
    public function testRawAlterError()
    {
        $this->expectException('PdoException');
        $this->expectExceptionMessage('multiple primary keys');

        $alter = 'ADD COLUMN active integer PRIMARY KEY';

        $result = $this->object->rawAlter('users', $alter);
    }

    /**
     * Test raw optimize
     *
     * @return void
     */
    public function testRawOptimize()
    {
        $result = $this->object->rawOptimize('users');

        $this->assertFalse($result);
    }

    /**
     * Test raw repair
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
            'host'     => POSTGRES_HOST,
            'user'     => POSTGRES_USER,
            'pass'     => POSTGRES_PASS,
            'db'       => POSTGRES_DB,
            'log'      => false,
            'log_file' => 'testdb.log',
        ];

        $this->createObject($cfg);

        $result = $this->object->log('a message');

        $this->assertFalse($result);
    }

    /**
     * Create test table
     *
     * @return void
     */
    protected function createTestTable()
    {
        $sql = "CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            name character varying(50),
            email character varying(50)
        );";

        $this->object->executeQuery($sql);
    }

    /**
     * Drop the test table
     *
     * @return void
     */
    protected function dropTestTable()
    {
        $sql = "DROP TABLE users;";

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
