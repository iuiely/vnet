<?php
namespace Vnet\console;
use Vnet\Singleton;

class Ip_lib{
    use Singleton;
    protected $ifname_path='/sys/class/net/';
    public function check_cidr($cidr){
        $result=[];
        $split_res = preg_split("/[\s\/]+/",$cidr);
        if(count($split_res)===2){
            $mask_format = explode(".",$split_res[1]);
            if(count($mask_format)===1){
                if($split_res[1]>7 && $split_res[1] <33){
                    $cidr = $split_res[0].DIRECTORY_SEPARATOR.$split_res[1];
                    $result[]=true;
                    $result[]=$cidr;
                    return $result;
                }else{
                    return $result[] =false;
                }
            }elseif(count($mask_format)===4){
                $mask_check = decoct(strstr(decbin(ip2long($split_res[1])),"0"));
                if($mask_check =='0'){
                    $h_mask = decbin(ip2long($split_res[1]));
                    $prefix=substr_count($h_mask,'1');
                    $cidr = $split_res[0].DIRECTORY_SEPARATOR.$prefix;
                    $result[]=true;
                    $result[]=$cidr;
                    return $result;
                }else{
                    return $result[] =false;
                }
            }else{
                return $result[] =false;
            }
        }else{ 
            return $result[] =false;
        }
    }
    public function get_device($ifname,$namespace=null){
        $result = [];
        if(empty($namespace) && !empty($ifname)){
            $result[] =is_dir($this->ifname_path.$ifname);
            $result[] = realpath($this->ifname_path.$ifname);
            return $result;
        }elseif(!empty($namespace) && !empty($ifname)){
            $command = 'ip netns exec '.$namespace.' ip -o link show '.$ifname.' |wc -l';
            exec($command,$res,$code);
            $result[]=$code;
            $result[]=$res;
            return $result;
        }else{
            return $result[] =false;
        }
    }
    public function get_master($ifname){
        $result = [];
        if(!empty($ifname)){
            $result[] =is_dir($this->ifname_path.$ifname.'/master');
            $result[] =realpath($this->ifname_path.$ifname.'/master');
            return $result;
        }else{
            return $result[] =false;
        }
    }
    public function get_vlan($vlan_name,$vlanid,$map_nic=null){
            var_dump($vlan_name,$vlanid,$map_nic);
    }
    public function get_vxlan($vxlan_name,$vxlanid,$map_nic=null){
        var_dump($vxlan_name,$vxlanid,$map_nic);
    }
    public function get_netns($namespace){
        $result = [];
        if(!empty($namespace)&&is_dir('/var/run/netns')){
            $result[] = is_file('/var/run/netns/'.$namespace);
            $result[] = realpath('/var/run/netns/'.$namespace);
            return $result;
        }else{
            return $result[] =false;
        }
    }
    public function set_up($ifname,$namespace=null){
        $result = [];
        if(empty($namespace)&& !empty($ifname)){
            $command = 'ip link set '.$ifname.' up 2>&1';
            exec($command,$res,$code);
            $result[]=$code;
            $result[]=$res;
            return $result;
        }elseif(!empty($namespace)&& !empty($ifname)){
            $check = $this->get_netns($namespace);
            if($check[0]){
                $command = 'ip netns exec '.$namespace.' ip link set '.$ifname.' up 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function create_vswitch($vswitch){
        $result = [];
        if(!empty($vswitch)){
            $check = $this->get_device($vswitch);
            if(!$check[0]){
                $command = 'ip link add dev '.$vswitch.' type bridge 2>&1';
                exec($command,$res,$code);
                $this->set_up($vswitch);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function create_nic($ifname){
        $result = [];
        if(!empty($ifname)){
            $check = $this->get_device($ifname);
            if(!$check[0]){
                $command = 'ip tuntap add '.$ifname.' mode tap 2>&1';
                exec($command,$res,$code);
                $this->set_up($ifname);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function create_veth($ifname1,$ifname2,$ifname1_mac=null,$ifname2_mac=null){
        $result = [];
        if(!empty($ifname1)&&!empty($ifname2)){
            $check = $this->get_device($ifname1);
            if(!$check[0]){
                $command = 'ip link add '.$ifname1.' type veth peer name '.$ifname2.' 2>&1';
                exec($command,$res,$code);
                if(!empty($ifname1_mac) && !empty($ifname2_mac)){
                    $this->set_if_address($ifname1,$ifname1_mac);
                    $this->set_if_address($ifname2,$ifname2_mac);
                }
                $this->set_up($ifname1);
                $this->set_up($ifname2);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    //  Creates a new vlan device $vlan_name on device $map_nic.this device's vlan id is $vlanid
    public function create_vlan($vlan_name,$vlanid,$map_nic){
        $result = [];
        if(!empty($vlan_name)&&!empty($vlanid)&& !empty($map_nic)&& is_int(intval($vlanid))){
            $check = $this->get_device($vlan_name);
            if(!$check[0]){
                $command = 'ip link add link '.$map_nic.' name '.$vlan_name.' type vlan id '.$vlanid.' 2>&1';
                exec($command,$res,$code);
                $this->set_up($vlan_name);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function create_vxlan($vxlan_name,$vxlanid,$map_nic,$group=null,$group_ip){
        var_dump($vxlan_name,$vxlanid,$map_nic,$group,$group_ip);
    }
    public function create_netns($namespace){
        $result = [];
        if(!empty($namespace)){
            $check = $this->get_netns($namespace);
            if(!$check[0]){
                $command = 'ip netns add '.$namespace.' 2>&1';
                exec($command,$res,$code);
                $this->set_up('lo',$namespace);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function add_ip_address($cidr,$ifname,$namespace=null,$scope='global',$brd=true){
        $result = [];
        if(empty($namespace) && !empty($cidr) && !empty($ifname)){
            if($this->get_device($ifname)[0] && $this->check_cidr($cidr)[0] && $brd){
                $command = 'ip address add '.$cidr.' brd + dev '.$ifname.' scope '.$scope.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }elseif($this->get_device($ifname)[0] && $this->check_cidr($cidr)[0] && !$brd){
                $command = 'ip address add '.$cidr.' dev '.$ifname.' scope '.$scope.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $result[] =false;
            }
        }elseif(!empty($namespace) && !empty($cidr) && !empty($ifname)){
            if(!$this->get_device($ifname,$namespace)[0] && $this->check_cidr($cidr)[0] && $brd){
                $command = 'ip netns exec '.$namespace.' ip address add '.$cidr.' brd + dev '.$ifname.' scope '.$scope.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }elseif(!$this->get_device($ifname,$namespace)[0] && $this->check_cidr($cidr)[0] && !$brd){
                $command = 'ip netns exec '.$namespace.' ip address add '.$cidr.' dev '.$ifname.' scope '.$scope.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $result[] =false;
            }        
        }else{
            return $result[] =false;
        }
    }
    public function remove_ip_address($cidr,$ifname,$namespace=null,$scope='global',$brd=true){
        $result = [];
        if(empty($namespace) && !empty($cidr) && !empty($ifname)){
            if($this->get_device($ifname)[0] && $this->check_cidr($cidr)[0] && $brd){
                $command = 'ip address delete '.$cidr.' brd + dev '.$ifname.' scope '.$scope.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }elseif($this->get_device($ifname)[0] && $this->check_cidr($cidr)[0] && !$brd){
                $command = 'ip address delete '.$cidr.' dev '.$ifname.' scope '.$scope.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $result[] =false;
            }
        }elseif(!empty($namespace) && !empty($cidr) && !empty($ifname)){
            if(!$this->get_device($ifname,$namespace)[0] && $this->check_cidr($cidr)[0] && $brd){
                $command = 'ip netns exec '.$namespace.' ip address delete '.$cidr.' brd + dev '.$ifname.' scope '.$scope.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }elseif(!$this->get_device($ifname,$namespace)[0] && $this->check_cidr($cidr)[0] && !$brd){
                $command = 'ip netns exec '.$namespace.' ip address delete '.$cidr.' dev '.$ifname.' scope '.$scope.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $result[] =false;
            }
        }else{
            return $result[] =false;
        }
    }
    public function remove_vswitch($vswitch){
        $result = [];
        if(!empty($vswitch)){
            $check = $this->get_device($vswitch);
            if($check[0]){
                $command = 'ip link delete '.$vswitch.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function remove_nic($ifname,$namespace=null){
        $result = [];
        if(empty($namespace) && !empty($ifname)){
            $check = $this->get_device($ifname);
            if($check[0]){
                $command = 'ip link delete '.$ifname.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function remove_netns($namespace){
        $result = [];
        if(!empty($namespace)){
            $check = $this->get_netns($namespace);
            if($check[0]){
                $command = 'ip netns delete '.$namespace.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function is_vswitch($ifname){
        $result=[];
        if(!empty($ifname)){
            $result[] =is_dir($this->ifname_path.$ifname.DIRECTORY_SEPARATOR.'bridge');
            $result[] = realpath($this->ifname_path.$ifname.DIRECTORY_SEPARATOR.'bridge');
            return $result;
        }else{
            return $result[] =false;
        }
    }
    public function add_if_in_bridge($ifname,$bridge){
        $result = [];
        if(!empty($ifname)&&!empty($bridge)){
            $check = $this->is_vswitch($bridge);
            if($this->is_vswitch($bridge)[0]){
                $command = 'ip link set '.$ifname.' master '.$bridge.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function del_if_out_bridge($ifname){
        $result = [];
        if(!empty($ifname)){
            $command = 'ip link set '.$ifname.' nomaster 2>&1';
            exec($command,$res,$code);
            $result[]=$code;
            $result[]=$res;
            return $result;
        }else{
            return $result[] =false;
        }
    }
    public function add_if_in_ns($ifname,$namespace){
        $result = [];
        if(!empty($namespace)&&!empty($ifname)){
            $check = $this->get_netns($namespace);
            if($check[0]){
                $command = 'ip link set '.$ifname.' netns '.$namespace.' 2>&1';
                exec($command,$res,$code);
                $this->set_up($ifname,$namespace);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
    public function del_if_out_ns($ifname,$namespace){
        $result = [];
        if(!empty($namespace)&&!empty($ifname)){
            if($this->get_netns($namespace)[0]){
                $command = 'ip netns exec '.$namespace.' ip link set '.$ifname.' netns 1 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
            }
            return $result;
        }else{
            return $result[] =false;
        }
    }
    public function set_if_address($ifname,$mac){
        $result = [];
        if(!empty($ifname) && !empty($mac)){
            $check = $this->get_device($ifname);
            if($check[0]){
                $command = 'ip link set '.$ifname.' address '.$mac.' 2>&1';
                exec($command,$res,$code);
                $result[]=$code;
                $result[]=$res;
                return $result;
            }else{
                return $check;
            }
        }else{
            return $result[] =false;
        }
    }
}
