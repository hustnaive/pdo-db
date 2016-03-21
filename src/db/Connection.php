<?php
namespace fangl\db;

/**
 * $db = new \fangl\db\Connection($dsn, $username, $password);
 * 
 * @author fangl
 *
 */
class Connection {
    
    private $dsn;
    
    private $username;
    
    private $password;
    
    private $charset;
    
    private $options;
    
    private $pdo;
    
    private $transactionIsBegan = false;
    
    public function __construct($dsn, $username, $password, $options=[], $charset='utf8')
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->charset = $charset;
    }
    
    public function isActive()
    {
        return $this->pdo !== null;
    }
    
    public function open()
    {
        if($this->isActive()) {
            return;
        }
        else {
            $this->pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            foreach($this->options as $k => $value) {
                $this->pdo->setAttribute($k, $value);
            }
            if ($this->charset !== null) {
                $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
            } 
        }
    }
    
    public function close()
    {
        if($this->transactionIsBegan) {
            throw new DbException('you may forgot to end the transaction by calling commit or rollback');
        }
        $this->pdo = null;
    }
    
    public function beginTransaction()
    {
        $this->open();
        $this->transactionIsBegan = true;
        return $this->pdo->beginTransaction();
    }
    
    public function commit()
    {
        $this->transactionIsBegan = false;
        return $this->pdo->commit();
    }
    
    public function rollBack()
    {
        $this->transactionIsBegan = false;
        return $this->pdo->rollBack();
    }
    
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * create a sql command to be executed
     * @param string $sql
     * @param array $params
     * @param string $autoClose whether to auto close the db after the sql been executed
     * @return \fangl\db\Command
     */
    public function createCommand($sql=null, $params=[], $autoClose=false)
    {
        $this->open();
        if($autoClose && $this->transactionIsBegan) {
            throw new DbException('there is a transaction block began here, you may move this code out of the block');
        }
        return new Command($this, $sql, $params, $autoClose);
    }
    
    /**
     * quote tthe value by pdo type
     * @param mix $value
     * @param string $type see ::getPdoType
     * @return string
     */
    public function quoteValue($value, $type=null)
    {
        $this->open();
        return $this->pdo->quote($value, $type == null ? $this->getPdoType($value):$type);
    }
    
    /**
     * return the pdo type of the data
     * @param mix $data
     * @return number
     */
    public function getPdoType($data)
    {
        static $typeMap = [
            // php type => PDO type
            'boolean' => \PDO::PARAM_BOOL,
            'integer' => \PDO::PARAM_INT,
            'string' => \PDO::PARAM_STR,
            'resource' => \PDO::PARAM_LOB,
            'NULL' => \PDO::PARAM_NULL,
        ];
        $type = gettype($data);
    
        return isset($typeMap[$type]) ? $typeMap[$type] : \PDO::PARAM_STR;
    }
    
    /**
     * quote the table name with `
     * @param string $name
     * @return string
     */
    public function quoteTable($name)
    {
        $name = trim((string)$name,'`');
        if(strpos($name, "`") !== false) {
            throw new DbException("table name must not contain any charcter `, {$name} is given");
        }
        else {
            return "`$name`";
        }
    }
    
    public function quoteColumn($name)
    {
        $name = trim((string)$name,'`');
        if(strpos($name, "`") !== false) {
            throw new DbException("column name must not contain any charcter `, {$name} is given");
        }
        else {
            return (string)$name === '*' ? (string)$name:"`$name`";
        }
    }
    
    public function getPdo($autoOpen = true)
    {
        if($autoOpen) {
            $this->open();
        }
        return $this->pdo;
    }
    
}