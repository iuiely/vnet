<?php
namespace Vnet\console;
//use Vnet\Singleton;
 
class Dhcp_lib{
//    use Singleton;
    public function add_dhcp_server($namespace,$ifname,$tag,$dhcprange,$netmask,$gateway,$dhcphostfile,$pidfile){
        $result = [];
        if(!empty($namespace) && !empty($ifname) && !empty($tag) && !empty($dhcprange) && !empty($netmask) && !empty($gateway) && !empty($dhcphostfile)){
            if(!is_dir('/var/run/netns')||!is_file('/var/run/netns/'.$namespace)){
                return $result[]=false;
            }           
            $command = 'ip netns exec '.$namespace.' ip -o link show '.$ifname.' |wc -l';
            exec($command,$res,$code);
            if($code !== 0 || $res[0] != '1'){
                $result[]=false;
                $result[]=$res;
                return $result;
            }
            if(!is_int(ip2long(explode(',',$dhcprange)[0]))||!is_int(ip2long(explode(',',$dhcprange)[1]))||!is_int(ip2long($netmask))||!is_int(ip2long($gateway))){
                return $result[] = false;
            }
            if(!file_exists($dhcphostfile)){
                return $result[] = false;
            }
            $command = 'ip netns exec '.$namespace.' dnsmasq --no-hosts --no-resolv --strict-order --no-ping --bind-interfaces --interface=';
            $command.= $ifname.' --except-interface=lo --pid-file='.$pidfile.' --dhcp-range=set:'.$tag.','.explode(',',$dhcprange)[0].',static,'.$netmask;
            $command.=',2h --dhcp-option=option:router,'.$gateway.' --dhcp-hostsdir='.$dhcphostfile.' 2>&1';
            unset($res,$code,$result);
            exec($command,$res,$code);
            if($code === 0 && file_exists('/proc/'.intval(file_get_contents($pidfile)))){
                $result[] = true;
                $result[] = $res;
                return $result;
            }else{
                $result[] = false;
                $result[] = $res;
                return $result;
            }
        }else{
            return $result[]=false;
        }
    }
    public function remove_dhcp_server($pidfile){
        if(!file_exists($pidfile)){
            return false;
        }
        $pid = intval(file_get_contents($pidfile));
        if(posix_kill($pid,SIG_DFL)){
            if($pid>0){
                posix_kill($pid,SIGTERM);
                if(!file_exists('/proc/'.$pid)){
                    return true;
                }else{
                    posix_kill($pid,SIGKILL);
                    return true;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}
