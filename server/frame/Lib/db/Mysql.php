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
        $this->connection = MysqlConnect::Newconnect($this->config);
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
    public function select($sql){
        $data = $this->connection->query($sql);
        $error = $this->connection->error;
        $result = array(
            "result"=> $data,
            "error" => $error
        );
        return $result;
    }
    public function insert($sql){
        $data = $this->connection->query($sql);
        $insert_id = $this->connection->insert_id;
        $affected_rows = $this->connection->affected_rows;
        $error = $this->connection->error;
        $result = array(
            "result"=> $data,
            "insert_id" =>$insert_id,
            "affected_rows"=>$affected_rows,
            "error" => $error
        );
        return $result;
    }
    public function update($sql){
        $data = $this->connection->query($sql);
        $affected_rows = $this->connection->affected_rows;
        $error = $this->connection->error;
        $result = array(
            "result"=> $data,
            "affected_rows"=>$affected_rows,
            "error" => $error
        );
        return $result;
    }
    public function delete($sql){
        $data = $this->connection->query($sql);
        $affected_rows = $this->connection->affected_rows;
        $error = $this->connection->error;
        $result = array(
            "result"=> $data,
            "affected_rows"=>$affected_rows,
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
/*
go(function (){
$config = [
        'host'     => '10.200.3.97',
        'port'     => 3306,
        'user'     => 'root',
        'password' => '123456',
        'database' => 'rest',
        'timeout'  => -1,
];    
var_dump($config);
$mysql = new Mysql($config);
});
*/
