<?php
class Database
{
    private $connection;
    private $statement;

    public function __construct()
    {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false
        ]);
    }

    public function query($sql){ $this->statement = $this->connection->prepare($sql); }
    public function bind($param,$value,$type=null){
        if($type===null){
            if(is_int($value)) $type=PDO::PARAM_INT;
            elseif(is_bool($value)) $type=PDO::PARAM_BOOL;
            elseif(is_null($value)) $type=PDO::PARAM_NULL;
            else $type=PDO::PARAM_STR;
        }
        $this->statement->bindValue($param,$value,$type);
    }
    public function execute(){ return $this->statement->execute(); }
    public function resultSet(){ $this->execute(); return $this->statement->fetchAll(); }
    public function single(){ $this->execute(); return $this->statement->fetch(); }
    public function lastInsertId(){ return $this->connection->lastInsertId(); }
}
