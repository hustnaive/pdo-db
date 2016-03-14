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
            [1,\PDO::PARAM_INT,"'1'"],
            [true,\PDO::PARAM_BOOL,"'1'"],
            [false,\PDO::PARAM_BOOL,"''"],
            ['ab"c',\PDO::PARAM_STR,"'ab\\\"c'"],
            ['ab\'c',\PDO::PARAM_STR,"'ab\'c'"]
        ];
        $db = $this->getDb(__METHOD__);
        foreach($values as $v) {
            $this->assertEquals($v[2], $db->quoteValue($v[0],$v[1]));
        }
    }
    
    public function testQuoteTable() {
        $tables = [
            ['abc','`abc`'],
            ['`abc','`abc`'],
            ['a`bc','`ab']
        ];
    }
    
    public function testQuoteColumn() {
        
    }
}