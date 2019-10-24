<?php
namespace Vnet\db;
use Vnet\Config;

class Subinfo{
    private static $_instance = null;
    protected $config=[];
    protected $connection;
    private function __clone(){}
    private function __wakeup(){}
    public function __construct(){
        $this->config = Config::getInstance()->get('config.subnets');
        $this->connection = new \Swoole\Coroutine\MySQL();
        $this->connection->connect($this->config);
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
    public function ipinfo($ip,$mask){
        $mask_format = explode(".",$mask);
        $mask_len = 32;
        if(count($mask_format)>1){
            $mask_check = decoct(strstr(decbin(ip2long($mask)),"0"));
            if ($mask_check == '0'){
                $ip = ip2long($ip);
                $mask = ip2long($mask);
                $networkaddress = $ip & $mask;
                $broadcast = $ip| (~$mask) & 0xFFFFFFFF;
                return array($ip,$mask,$networkaddress,$broadcast);
            }else{
                return false;
            }
        }else{
            $ip = ip2long($ip);
            $mask = 0xFFFFFFFF << (32-$mask) & 0xFFFFFFFF;
            $networkaddress = ($ip & $mask);
            $broadcast = $ip| (~$mask) & 0xFFFFFFFF;
            return array($ip,$mask,$networkaddress,$broadcast);
        }
    }
    
    public function iparray($ip){
        $ip_res = array();
        $split_pattern='/';
        $ip_split = preg_split("/[\s\/]+/",$ip);
        if(count($ip_split) == 1){
            return json_encode(-1);
        }else{
            list($ip,$mask) = explode($split_pattern,$ip);
            $ip_return = $this->ipinfo($ip,$mask);
            if($ip_return === false){
                return json_encode(-1);
            }else{
                list($res_ip, $netmask, $networkaddress, $broadcast) = $ip_return;
                foreach(range($networkaddress+1,$broadcast-1) as $ip_list){
                    $ip_res1[0] = long2ip($ip_list);
                    $ip_res1[1] = long2ip($netmask);
                    $ip_res[] = $ip_res1;
                }
                return $ip_res;
            }
        }
    }
} 
