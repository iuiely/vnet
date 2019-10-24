<?php
namespace Vnet\db;
use Vnet\Config;

class Mysqli{
    protected $config=[];
    protected $host;
    protected $user;
    protected $password;
    protected $database;
    protected $port=3306;
    protected $connection;
    private function __clone(){}
    private function __wakeup(){}
    public function __construct(array $config=[]){
        if(!empty($config)){
            $this->config = array_merge($this->config,$config);
        }else{
            $this->config = config::getInstance()->get('config.mysql');
        }
        $this->connect($this->config);
    }
    public function connect($config){
        if(!empty($config)){
            $this->host=$config['host'];
            $this->user=$config['user'];
            $this->password=$config['password'];
            $this->database = $config['database'];
            $this->port = $config['port'];
            $this->connection = new \mysqli($this->host,$this->user,$this->password,$this->database,$this->port);
            if(!$this->connection->connect_errno){
                return $this->connection;
            }else{
                return false;
            }
        }
        return false;
    }
    public function select($sql){
        $result = $this->connection->query($sql);
        $data=array();
        while($row = $result->fetch_assoc()){
            $value[] = $row;
            $data['data'] = $value;
        }
        $data['num_rows'] = $result->num_rows;
        $result->close();
        return $data;
    }
    public function insert($sql){
        $res = $this->connection->query($sql);
        $result['result'] = $res;
        $result['insert_id'] = $this->connection->insert_id;
        $result['affected_rows'] = $this->connection->affected_rows;
        $result['error'] = $this->connection->errno;
        $this->connection->close();
        return $result;
    }
    public function update($sql){
        $res = $this->connection->query($sql);
        $result['result'] = $res;
        $result['affected_rows'] = $this->connection->affected_rows;
        $result['error'] = $this->connection->errno;
        $this->connection->close();
        return $result;
    }
    public function delete($sql){
        $res = $this->connection->query($sql);
        $result['result'] = $res;
        $result['affected_rows'] = $this->connection->affected_rows;
        $result['error'] = $this->connection->errno;
        $this->connection->close();
        return $result;
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
$config = [
    'host' => '10.200.3.97',
    'port' => 3306,
    'user' => 'root',
    'password' => '123456',
    'database' => 'test',
];
$con=new Mysqli($config);
$data = $con->select('select * from user');
//$data = $con->update("update user set name='aaa3' where id=6");
//var_dump($data);
//$data =$con->insert("insert into user(name)value('aaa4'),('aaa5')");
foreach($data['data'] as $value){
    var_dump($value);
}
//$data = $con->delete("delete from user where id in (5)");
//var_dump($value);
*/
