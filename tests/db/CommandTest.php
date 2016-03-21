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
        $db = $this->getDb();
        $ret = $db->createCommand('select * from user')->queryOne();
        $this->assertInstanceOf(\PDO::class, $db->getPdo(false));
    }
    
    public function testQueryOneAutoClose() {
        $db = $this->getDb();
        $db->createCommand('select * from user',[],true)->queryOne();
        $this->assertNull($db->getPdo(false));
    }
    
    public function testQueryAll() {
        $db = $this->getDb();
        $db->createCommand('select name from user')->queryAll();
        $this->assertInstanceOf(\PDO::class, $db->getPdo(false));
    }
    
    public function tetQueryAllAutoClose() {
        $db = $this->getDb();
        $db->createCommand('select name from user',[],true)->queryAll();
        $this->assertNull($db->getPdo(false));
    }

    public function testQueryColumn() {
        $db = $this->getDb();
        $db->createCommand('select name from user')->queryColumn();
        $this->assertInstanceOf(\PDO::class, $db->getPdo(false));
    }
    
    public function testQueryColumnAutoClose() {
        $db = $this->getDb();
        $db->createCommand('select name from user', [], true)->queryColumn();
        $this->assertNull($db->getPdo(false));
        $this->assertInstanceOf(\PDO::class, $db->getPdo());
    }
    
    public function testExecute() {
        $db = $this->getDb();
        $db->createCommand('delete from user where id = :ID',[':ID'=>3])->execute();
        $this->assertInstanceOf(\PDO::class, $db->getPdo(false));
    }
    
    public function testExecuteAutoClose() {
        $db = $this->getDb();
        $db->createCommand('delete from user where id = :ID',[':ID'=>3],true)->execute();
        $this->assertNull($db->getPdo(false));
    }
    
    public function testInsert() {
        $db = $this->getDb();
        $command = $db->createCommand();
        $command->insert('user', ['id'=>time(),'name'=>'name','tel'=>123])->execute();
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