<?php
/**
 * Qi_Db_PdoSqlite Test class file
 *
 * @package Qi
 */

/**
 * Qi_Console_PdoSqlite Test class
 *
 * @uses BaseTestCase
 * @package Qi
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qi_Db_PdoSqliteTest extends BaseTestCase
{
    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp()
    {
        $cfg = array(
            'dbfile'   => 'test.db3',
            'log'      => true,
            'log_file' => 'testdb.log',
        );

        $this->_createObject($cfg);
        $this->_createTestTable();
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown()
    {
        @unlink('test.db3');
        @unlink('data.db3');
        @unlink('test.db2');
        @unlink('testdb.log');
    }

    /**
     * Create object
     *
     * @param array $cfg Config
     * @return void
     */
    protected function _createObject($cfg)
    {
        $this->_object = new Qi_Db_PdoSqlite($cfg);
    }

    /**
     * Constructor test with empty array
     *
     * @return void
     */
    public function testConstructEmptyArgs()
    {
        $cfg = array();

        $this->_createObject($cfg);
        $this->assertTrue(is_object($this->_object));
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
        $cfg = array(
            'dbfile'  => 'test.db2',
            'version' => '2',
        );

        $this->_createObject($cfg);
        $this->assertTrue(is_object($this->_object));
    }

    /**
     * Test initializing connection to db file to a folder without write
     * permissions
     *
     * @expectedException Qi_Db_PdoException unable
     * @return void
     */
    public function testConstructToFolderWithoutWritePerms()
    {
        $cfg = array(
            'dbfile' => '/etc/testsqlite.db3',
        );

        $this->_createObject($cfg);
    }

    /**
     * testConstructWithBadParams
     *
     * @expectedException Qi_Db_PdoException
     * @return void
     */
    public function testConstructWithBadParams()
    {
        $cfg = array(
            'dbfile' => ':mysql:dbname=testdb;unix_socket=/path/to/socket',
        );

        $this->_createObject($cfg);
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

        $this->assertEquals(array(), $r);
    }

    /**
     * Create table once it was already called
     *
     * @return void
     * @expectedException Qi_Db_PdoException already 1
     */
    public function testCreateTableTwice()
    {
        $this->_createTestTable();
    }

    /**
     * Test safe query with data binding
     *
     * @return void
     */
    public function testRawQueryWithDataBinding()
    {
        $sql = "insert into users (name, email) values (?, ?)";

        $data = array('jansen', 'jansen@test.com');

        $expected = array(
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $r = $this->_object->executeQuery($sql, $data);

        $sql = "select * from users";

        $actual = $this->_object->fetchRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * testInvalidQuery
     *
     * @expectedException Qi_Db_PdoException such
     * @return void
     */
    public function testInvalidQuery()
    {
        $sql = "SELECT * FROM foobar WHERE email=?";

        $data = array();

        $result = $this->_object->executeQuery($sql, $data);
    }

    /**
     * Test invalid statement
     *
     * @return void
     */
    public function testInvalidStatement()
    {
    }

    /**
     * Test raw insert
     *
     * @return void
     */
    public function testRawInsert()
    {
        $set = "('name', 'email') values ('jansen', 'jansen@test.com')";

        $expected = array(
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $r = $this->_object->rawInsert('users', $set);

        $sql = "select * from users";

        $actual = $this->_object->fetchRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * Test raw insert with error
     *
     * @expectedException Qi_Db_PdoException syntax 1
     * @return void
     */
    public function testRawInsertWithError()
    {
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
        $data = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $expected = array(
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

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
     * @expectedException Qi_Db_PdoException flaxx 1
     */
    public function testInsertWithExtraColumn()
    {
        $data = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
            'flaxx' => 'none',
        );

        $r = $this->_object->insert('users', $data);
        $this->assertFalse($r);
    }

    /**
     * Test safe update
     *
     * @return void
     */
    public function testRawUpdate()
    {
        $response = $this->_object->rawUpdate(
            'users', "name='orihah'", 'id=?',
            array(1)
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
        $data = array(
            'name' => 'orihah',
            'email' => 'orihah@test.com',
        );

        $response = $this->_object->update('users', $data, 'id=?', array(1));
        $this->assertTrue($response);

    }

    public function testDelete()
    {
        $response = $this->_object->delete('users', 'id=?', array(1));
        $this->assertTrue($response);
    }

    /**
     * Test safe delete
     *
     * @return void
     */
    public function testRawDelete()
    {
        $response = $this->_object->rawDelete('users', 'id=?', array(1));
        $this->assertTrue($response);
    }

    /**
     * Test safe field
     *
     * @return void
     */
    public function testSimpleFetchValue()
    {
        $this->_populateTestData();
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
        $this->_populateTestData();
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
        $this->_populateTestData();
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
        $this->_populateTestData();
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
        $this->_populateTestData();

        $result = $this->_object->simpleFetchRow('name,email', 'users', 'id=1');

        $expected = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe row no results
     *
     * @return void
     */
    public function testSimpleFetchRowNoResults()
    {
        $this->_populateTestData();

        $result = $this->_object->simpleFetchRow('name,email', 'users', 'id=22');

        $this->assertEquals(array(), $result);
    }

    /**
     * Test safe row only one
     *
     * @return void
     */
    public function testSimpleFetchRowOnlyOne()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $result = $this->_object->simpleFetchRow('name,email', 'users', '');

        $expected = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe rows
     *
     * @return void
     */
    public function testSimpleFetchRows()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $result = $this->_object->simpleFetchRows('name,email', 'users', '');

        $expected = array(
            array(
                'name' => 'jansen',
                'email' => 'jansen@test.com',
            ),
            array(
                'name' => 'orihah',
                'email' => 'orihah@test.com',
            ),
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe rows no records
     *
     * @return void
     */
    public function testSimpleFetchRowsNoRecords()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $result = $this->_object->simpleFetchRows('name,email', 'users', 'id > 22');

        $expected = array();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test get count
     *
     * @return void
     */
    public function testGetCount()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

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
        $this->_populateTestData();
        $this->_populateMoreTestData();

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
        $this->_populateTestData();
        $this->_populateMoreTestData();

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
        $alter = 'ADD COLUMN active integer';

        $result = $this->_object->rawAlter('users', $alter);

        $statement = $this->_object->executeQuery("PRAGMA table_info('users')");

        $pragma = $statement->fetchAll(PDO::FETCH_ASSOC);

        // This is the expected third column (row in pragma array)
        $expected = array(
            'cid'        => '3',
            'name'       => 'active',
            'type'       => 'integer',
            'notnull'    => '0',
            'dflt_value' => '',
            'pk'         => '0',
        );

        $this->assertEquals($expected, $pragma[3]);
    }

    /**
     * Test raw alter with an error
     *
     * @expectedException Qi_Db_PdoException Cannot
     * @return void
     */
    public function testRawAlterError()
    {
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

        $this->assertFalse($result);
    }

    /**
     * Test safe repair
     *
     * @return void
     */
    public function testRawRepair()
    {
        $result = $this->_object->rawRepair('users');

        $this->assertFalse($result);
    }

    /**
     * Test get row no results
     *
     * @return void
     */
    public function testFetchRowNoResults()
    {
        $this->_populateTestData();
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
        $data = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $expected = array(
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $r = $this->_object->insert('users', $data);

        $q = "select * from users where id=?";

        $data = array('1');

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

        $this->assertEquals(array(''), $result);
    }

    /**
     * Test the log method
     *
     * @return void
     */
    public function testLog()
    {
        $cfg = array(
            'dbfile'   => 'test.db3',
            'log'      => false,
            'log_file' => 'testdb.log',
        );

        $this->_createObject($cfg);

        $result = $this->_object->log('a message');

        $this->assertFalse($result);
    }

    /**
     * Create test table
     *
     * @return void
     */
    protected function _createTestTable()
    {
        $sql = "create table users (
            'id' integer primary key,
            'name' text,
            'email' text
        );";

        $this->_object->executeQuery($sql);
    }

    /**
     * Populate test data
     *
     * @return void
     */
    protected function _populateTestData()
    {
        $data = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        return $this->_object->insert('users', $data);
    }

    /**
     * Populate more test data
     *
     * @return void
     */
    protected function _populateMoreTestData()
    {
        $data = array(
            'name'  => 'orihah',
            'email' => 'orihah@test.com',
        );

        return $this->_object->insert('users', $data);
    }
}
