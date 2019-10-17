<?php
namespace Vnet\db;
use Vnet\db\MysqlConnect;
use Vnet\Config;

class Mysql{

    protected $config = [];
    protected $connection;
    public function __construct(array $config=[]){
        if(!empty($config)){
            $this->config = $config;
            $this->connect();
        }elseif(empty($config)){
            $this->config = Config::getInstance()->get('config.mysql');
            $this->connect();
        }
    }
    public function connect(){
        $this->connection = MysqlConnect::getInstance($this->config)->Newconnect();
        return $this->connection;
    }
    public function query($sql){
        $data = $this->connection->query($sql);
        $affected_rows = $this->connection->affected_rows;
        $insert_id = $this->connection->insert_id;
        $errno = $this->connection->errno;
        $error = $this->connection->error;
        $result = array(
            "result"=> $data,
            "affected_rows"=>$affected_rows,
            "insert_id" =>$insert_id,
            "errno" => $errno,
            "error" => $error
        );
        return $result;
    }
    public function affected_rows(){
        return $this->connection->affected_rows;
    }
    public function insert_id(){
        return $this->connection->insert_id;
    }
    public function errno(){
        return $this->connection->errno;
    }
    public function error(){
        return $this->connection->error;
    }
    public function begin(){
        $this->connection->begin();
    }
    public function commit(){
        $this->connection->commit();
    }
    public function rollback(){
        $this->connection->rollback();
    }
    public function recyle(){
        $this->connection->autorecycle($this->config['pool']['waittime'],$this->config['pool']['interval']);
    }
}
