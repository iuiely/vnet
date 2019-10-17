<?php
namespace subnet;

class Subinfo{
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
    
    public function subtabcreate($link,$tabname){
        $sql = "CREATE TABLE  $tabname( id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,ip VARCHAR(16) NOT NULL,mask VARCHAR(16) NOT NULL,gateway VARCHAR(16) NOT NULL,mac VARCHAR(19) NOT NULL,stat tinyint(4) NOT NULL,UNIQUE (ip))ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $result = $link->query($sql);
        if($result['result'] === true){
            return json_encode(1);
        }else{
            return json_encode(-1);
        }
        $link->close();
    }
} 
