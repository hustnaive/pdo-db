<?php
namespace tests\db;

class CommandTest extends \PHPUnit_Framework_TestCase {
    
    private $dsn = 'mysql:host=localhost;port=3306;dbname=test';
    private $username = 'root';
    private $password = '';
    
    private function getDb($method=null) {
        return new \fangl\db\Connection($this->dsn, $this->username, $this->password);
    }
    
    public function testQueryOne() {
        $ret = $this->getDb()->createCommand('select * from user')->queryOne();
        var_dump($ret);
    }
    
    public function testQueryAll() {
        $ret = $this->getDb()->createCommand('select name from user')->queryAll();
        var_dump($ret);
    }
    
    public function testQueryColumn() {
        $ret = $this->getDb()->createCommand('select name from user')->queryColumn();
        var_dump($ret);
    }
    
    public function testExecute() {
        $ret = $this->getDb()->createCommand('delete from user where id = :ID',[':ID'=>3])->execute();
        var_dump($ret);
    }
    
    public function testInsert() {
        $db = $this->getDb();
        $command = $db->createCommand();
        $command->insert('user', ['id'=>time(),'name'=>'name','tel'=>123])->execute();
        var_dump($db->lastInsertId());
        var_dump($command->lastSql());
    }
    
    public function testUpdate() {
        $this->getDb()->createCommand()->update('user', ['tel'=>'tel'], 'id = :ID', [':ID'=>2])->execute();
    }
    
    public function testDelete() {
        $this->getDb()->createCommand()->delete('user', 'id = :ID', [':ID'=>2])->execute();
    }
    
    public function testTruncate() {
        $this->getDb()->createCommand()->truncateTable('user')->execute();
    }
    
    public function testBatchInsert() {
        $users = [['id'=>1,'name'=>'test1','tel'=>123],['id'=>2,'tel'=>123],['id'=>3,'name'=>'test3','tel'=>123]];
        $this->getDb()->createCommand()->batchInsert('user', $users);
    }
}