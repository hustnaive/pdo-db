<?php
namespace fangl\db;

class Connection {
    
    private $dsn;
    
    private $username;
    
    private $password;
    
    private $charset;
    
    private $options;
    
    private $pdo;
    
    private $transaction;
    
    public function __construct($dsn, $username, $password, $options=[], $charset='utf8')
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
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
            if ($this->charset !== null) {
                $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
            } 
        }
    }
    
    public function close()
    {
        $this->pdo = null;
    }
    
    public function beginTransaction()
    {
        $this->open();
        return $this->pdo->beginTransaction();
    }
    
    public function commit()
    {
        return $this->pdo->commit();
    }
    
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
    
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    public function createCommand($sql=null,$params=[])
    {
        $this->open();
        return new Command($this, $sql, $params);
    }
    
    public function quoteValue($value, $type=null)
    {
        $this->open();
        return $this->pdo->quote($value, $type == null ? $this->getPdoType($value):$type);
    }
    
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
    
    public function quoteTable($name)
    {
        return strpos(trim($name,'`'), "`") !== false ? $name : "`" . $name . "`";
    }
    
    public function quoteColumn($name)
    {
        return strpos(trim($name,'`'), '`') !== false || $name === '*' ? $name : '`' . $name . '`';
    }
    
    public function getPdo()
    {
        return $this->pdo;
    }
    
}