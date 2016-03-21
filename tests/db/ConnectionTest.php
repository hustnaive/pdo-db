<?php
namespace tests\db;

class ConnectionTest extends \PHPUnit_Framework_TestCase {
    
    private $dsn = 'mysql:host=localhost;port=3306;dbname=test';
    private $username = 'root';
    private $password = '';
    
    private function getDb($method=null) {
        return new \fangl\db\Connection($this->dsn, $this->username, $this->password);
    }
    
    public function testOpen () {
        $db = $this->getDb(__METHOD__);
        $db->open();
        $this->assertTrue($db->isActive());
    }
    
    public function testQuoteValue() {
        $values = [
            [1,"'1'"],
            [true,"'1'"],
            [false,"''"],
            ['ab"c',"'ab\\\"c'"],
            ['ab\'c',"'ab\'c'"]
        ];
        $db = $this->getDb(__METHOD__);
        foreach($values as $v) {
            $this->assertEquals($v[1], $db->quoteValue($v[0]));
        }
    }
    
    public function testQuoteTable() {
        $tables = [
            ['abc','`abc`'],
            ['`abcd','`abcd`'],
            ['abcd`','`abcd`'],
//             ['a`bcde','`a`bcde`']  exception see ::testQuoteTableException
        ];
        
        $db = $this->getDb(__METHOD__);
        foreach ($tables as $t) {
            $this->assertEquals($t[1], $db->quoteTable($t[0]));
        }
    }
    
    /**
     * @expectedException \fangl\db\DbException
     */
    public function testQuoteTableException() {
        $db = $this->getDb(__METHOD__);
        $db->quoteTable('a`bcde');
    }
    
    public function testQuoteColumn() {
        $tables = [
            ['abc','`abc`'],
            ['`abcd','`abcd`'],
            ['abcd`','`abcd`'],
            ['*','*']
            //             ['a`bcde','`a`bcde`']  exception see ::testQuoteColumnException
        ];
        
        $db = $this->getDb(__METHOD__);
        foreach ($tables as $t) {
            $this->assertEquals($t[1], $db->quoteColumn($t[0]));
        }
    }
    
    /**
     * @expectedException \fangl\db\DbException
     */
    public function testQuoteColumnException() {
        $db = $this->getDb(__METHOD__);
        $db->quoteColumn('a`bcde');
    }
}