<?php
namespace fangl\db;

class Command {
    
    /**
     * 
     * @var \fangl\db\Connection
     */
    private $db;
    
    private $sql;
    
    private $params;
    
    private $autoClose;
    
    private $fetchMode = \PDO::FETCH_ASSOC;  //\PDO::FETCH_ASSOC, \PDO::FETCH_BOTH
    
    private $lastSql;
    
    /*
     * @var \PDOStatement
     */
    private $pdoStatement;
    
    public function __construct(\fangl\db\Connection $db, $sql=null, $params=[], $autoClose=false)
    {
        $this->db = $db;
        $this->sql = $sql;
        $this->params = $params;
        $this->autoClose = $autoClose;
    }
    
    public function lastSql()
    {
        return $this->lastSql;
    }
    
    protected function queryInternal($method, $fetchMode = null)
    {
        $this->prepare();

        $this->pdoStatement->execute();
    
        if ($fetchMode === null) {
            $fetchMode = $this->fetchMode;
        }
        $result = call_user_func_array([$this->pdoStatement, $method], (array) $fetchMode);
        $this->pdoStatement->closeCursor();
        $this->pdoStatement = null;
        if($this->autoClose) {
            $this->db->close();
        }
        return $result;
    }
    
    protected function bindPendingParams()
    {
        foreach ($this->params as $name => $value) {
            $v = is_array($value)?$value[0]:$value;
            $t = is_array($value) && isset($value[1]) ? $value[1]:$this->db->getPdoType($v); // 支持： ['name'=>['fangl',\PDO::PARAM_STR]] 这种格式以自定义参数类型
            $this->pdoStatement->bindValue($name, $v, $t);
        }
        $this->params = [];
    }
    
    /**
     * prepare the sql and bind params
     * @throws DbException
     * @return \fangl\db\Command
     */
    protected function prepare()
    {
        if($this->pdoStatement) {
            $this->bindPendingParams();
        }
        else {
            if($this->sql) {
                $this->pdoStatement = $this->db->getPdo()->prepare($this->sql);
                $this->lastSql = $this->sql;
                $this->sql = null;
                $this->bindPendingParams();
            }
            else throw new DbException('no sql to prepare');
        }
        return $this;
    }
    
    /**
     * excute the sql command, may throws \PDOException
     * @return int
     */
    public function execute()
    {
        $this->prepare();
        $this->pdoStatement->execute();
        $n = $this->pdoStatement->rowCount();
        $this->pdoStatement = null;
        if($this->autoClose) {
            $this->db->close();
        }
        return $n;
    }
    
    /**
     * fetch the first row of the sql result collections
     * @param int $fetchMode
     * @return mixed
     */
    public function queryOne($fetchMode=null)
    {
        return $this->queryInternal('fetch', $fetchMode);
    }
    
    /**
     * fetch all rows of the sql result collections
     * @param int $fetchMode
     * @return mixed
     */
    public function queryAll($fetchMode=null)
    {
        return $this->queryInternal('fetchAll', $fetchMode);
    }
    
    /**
     * fetch all the rows of first column of the sql result collections
     * return it as an array
     * for example:
     * if the result collection is [['name'=>'a'],['name'=>'b']], then this method will return ['a','b']
     * @return mixed
     */
    public function queryColumn()
    {
        return $this->queryInternal('fetchAll', \PDO::FETCH_COLUMN);
    }
    
    public function queryScalar()
    {
        $result = $this->queryInternal('fetchColumn', 0);
        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        } else {
            return $result;
        }
    }
    
    /**
     * 
     * @param string $table
     * @param array $columns
     * @return string|\fangl\db\Command
     */
    public function insert($table, $columns)
    {
        $fields = array_map(function($value) { return $this->db->quoteColumn($value);}, array_keys($columns));
        $placeholder = array_map(function($value) { return ':'.strtoupper($value);}, array_keys($columns));
        $this->sql = 'INSERT INTO '.$this->db->quoteTable($table).' ('.implode(',',$fields).') VALUES ('.implode(',',$placeholder).')';
        $this->params = [];
        foreach($columns as $k=>$v) {
            $this->params[':'.strtoupper($k)] = $v;
        }
        return $this->prepare();
    }
    
    /**
     * 
     * @param string $table
     * @param array $columns
     * @param string $condition
     * @param array $params to be bound in condition,could be set to empty array
     * @throws DbException
     * @return string|\fangl\db\Command
     */
    public function update($table, $columns, $condition, $params=[])
    {
        $sets = array_map(function($value) { return $this->db->quoteColumn($value).' = :'.strtoupper($value); }, array_keys($columns));
        $this->sql = 'UPDATE '.$this->db->quoteTable($table).' SET '.implode(',',$sets);
        if(is_string($condition)) {
            $this->sql .= ' WHERE '.$condition;
        }
        else throw new DbException('condition must be a string');
        
        $this->params = $params;
        foreach($columns as $k=>$v) {
            $this->params[':'.strtoupper($k)] = $v;
        }
        return $this->prepare();
    }
    
    /**
     * delete from a table by some condition
     * @param string $table
     * @param string $condition
     * @param array $params to be bound in condition
     * @throws DbException
     * @return \fangl\db\Command
     */
    public function delete($table, $condition, $params=[])
    {
        $this->sql = 'DELETE FROM '.$this->db->quoteTable($table);
        if(is_string($condition)) {
            $this->sql .= ' WHERE '.$condition;
        }
        else throw new DbException('condition must be a string');
        $this->params = $params;
        return $this->prepare();
    }
    
    /**
     * truncate a table
     * @param string $table
     * @return \fangl\db\Command
     */
    public function truncateTable($table)
    {
        $this->sql = 'TRUNCATE TABLE '.$this->db->quoteTable($table);
        $this->params = [];
        return $this->prepare();
    }
    
    /**
     * batchinsert some data
     * @param string $table
     * @param array $data
     * @param array $fields if empty ,will use array_keys($data[0]) as the fields
     * @throws Exception
     * @return string
     */
    public function batchInsert($table, $data, $fields=[])
    {
        if(empty($fields)) {
            $fields = array_keys($data[0]);
        }
    
        $this->db->beginTransaction();
        try {
            $this->sql = 'INSERT INTO '.$this->db->quoteTable($table).' ('.implode(',',$fields).') VALUES ';
            $this->params = [];
            $sqls = [];
            foreach($data as $i=>$columns) {
                $placeholder = array_map(function($value) use($i) { return ':'.strtoupper($value).$i;}, $fields);
                foreach($fields as $k) {
                    $this->params[':'.strtoupper($k).$i] = isset($columns[$k])?$columns[$k]:null;
                }
                $sqls[] = '('.implode(',', $placeholder).')';
            }
            $this->sql .= implode(',',$sqls);
            $this->execute();
            if($this->autoClose) {
                $this->db->close();
            }
        }
        catch(\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
        $this->db->commit();
    }
}