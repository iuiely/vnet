<?php
namespace server;
use Vnet\console\Dhcp_lib;
use Vnet\console\Ip_lib;
use Vnet\HandleFilesystem;
use Vnet\db\Mysqli;
use Vnet\db\Mysql;
use Vnet\redis\Redis;

class Subnet_set{
    public function create($data){
        $vlan_mode = config('config.network.enable_vlan');
        $vxlan_mode = config('config.network.enable_vxlan');
        $net_id=$data['net_id'];
        $sub_id=$data['sub_id'];
        $mode = $data['network_mode'];
        $brname=$data['brname'];
        $dhcp_namespace = $data['dhcp_namespace'];
        $my_ip = config('config.network.my_ip');
        if($mode =='vlan' && $vlan_mode && iplib()->get_netns($dhcp_namespace)[0] && iplib()->get_device($brname)[0]){
            $map_device_interface = config('config.network.vlan_map_interface');
            $dhcp_host=json_decode($data['dhcp_info'],true)['my_ip'];
            if($my_ip != $dhcp_host){
                $result['my_ip'] = $my_ip;
                $result['dhcp_host'] = $dhcp_host;
                return json_encode($result);
            }
            $dhcp_outif = json_decode($data['dhcp_info'],true)['out_if'];
            $dhcp_outmac=json_decode($data['dhcp_info'],true)['out_mac'];
            $dhcp_inif = json_decode($data['dhcp_info'],true)['in_if'];
            $dhcp_inmac = json_decode($data['dhcp_info'],true)['in_mac'];
            $vlanid=$data['vlan_id'];
            $vlanname=$map_device_interface.'.'.$vlanid;
            $gateway= $data['gateway'];
            $dhcp_range=$data['dhcp_range'];
            $if_ip_add=json_decode($data['dhcp_info'],true)['dhcpcidr'];
            $netmask = explode('/',$if_ip_add)[1];

            $add_vlan = iplib()->create_vlan($vlanname,$vlanid,$map_device_interface);
            $add_veth = iplib()->create_veth($dhcp_outif,$dhcp_inif,$dhcp_outmac,$dhcp_inmac);
            $vlan_in_bridge = iplib()->add_if_in_bridge($vlanname,$brname);
            $veth_in_bridge = iplib()->add_if_in_bridge($dhcp_outif,$brname);
            $veth_in_ns = iplib()->add_if_in_ns($dhcp_inif,$dhcp_namespace);
            $veth_set_ip = iplib()->add_ip_address($if_ip_add,$dhcp_inif,$dhcp_namespace);
            $dhcp_hostsdir=DHCP_FILE_PATH.DIRECTORY_SEPARATOR.$net_id.DIRECTORY_SEPARATOR.$sub_id;
            $dhcp_pidfile=DHCP_FILE_PATH.DIRECTORY_SEPARATOR.'pid'.DIRECTORY_SEPARATOR.$net_id.DIRECTORY_SEPARATOR.$sub_id;
            $add_dir = handlefile()->Add_dir($dhcp_hostsdir);
            $add_file = handlefile()->Add_file($dhcp_pidfile);
            $dhcp_hostsfile = $dhcp_hostsdir.DIRECTORY_SEPARATOR.$sub_id;
            $subnet_config=config('config.subnets');
            $sql = "select ip,mac from $sub_id where stat!='1'";
            $mysql = new Mysql($subnet_config);
            $result = $mysql->select($sql);
            $ip_mac_row ='';
            if(count($result['result'])){
                foreach($result['result'] as $row){
                    $ip_mac_row .= $row['ip'].','.$row['mac'].',VM-'.implode('-',explode('.',$row['ip']))."\n";
                }
            }
            file_put_contents($dhcp_hostsfile,$ip_mac_row);
            $add_dhcp_server=dhcplib()->add_dhcp_server($dhcp_namespace,$dhcp_inif,$data['subname'],$dhcp_range,$netmask,$gateway,$dhcp_hostsdir,$dhcp_pidfile);
            if($add_vlan[0]===0&&$add_veth[0]===0&&$vlan_in_bridge[0]===0&&$veth_in_bridge[0]===0&&$veth_in_ns[0]===0&&$veth_set_ip[0]===0 && $add_dir && $add_file && $add_dhcp_server[0]){
                $network_add_config = config('config.network_add');
                $redis = new Redis($network_add_config);
                $key = $my_ip.'-'.$data['subname'];
                $msg = json_encode($data);
                $redis->set($key,$msg,240);
                $network_run_config = config('config.network_run');
                $redis = new Redis($network_run_config);
                $redis->set($key,'running',120);
                return 1;
            }else{
                $result['add_vlan']=$add_vlan;
                $result['add_veth']=$add_veth;
                $result['vlan_in_bridge']=$vlan_in_bridge;
                $result['veth_in_bridge']=$veth_in_bridge;
                $result['veth_in_ns']=$veth_in_ns;
                $result['veth_set_ip']=$veth_set_ip;
                $result['add_dir']=$add_dir;
                $result['add_file']=$add_file;
                $result['add_dhcp_server']=$add_dhcp_server;
                dhcplib()->remove_dhcp_server($dhcp_pidfile);
                handlefile()->Delfile($dhcp_pidfile);
                handlefile()->Deldir($dhcp_hostsdir);
                iplib()->remove_ip_address($if_ip_add,$dhcp_inif,$dhcp_namespace);
                iplib()->del_if_out_ns($dhcp_inif,$dhcp_namespace);
                iplib()->remove_nic($dhcp_outif);
                iplib()->remove_nic($vlanname);
                return json_encode($result);
            }
        }
    }
    public function del($data){
        $net_id=$data['net_id'];
        $sub_id=$data['sub_id'];
        $brname=$data['brname'];
        $dhcp_namespace = $data['dhcp_namespace'];
        $map_device_interface = config('config.network.vlan_map_interface');
        $my_ip = config('config.network.my_ip');
        $dhcp_host=json_decode($data['dhcp_info'],true)['my_ip'];
        if($my_ip != $dhcp_host){
            $result['my_ip'] = $my_ip;
            $result['dhcp_host'] = $dhcp_host;
            return json_encode($result);
        }
        $dhcp_outif = json_decode($data['dhcp_info'],true)['out_if'];
        $dhcp_inif = json_decode($data['dhcp_info'],true)['in_if'];
        $vlanid=$data['vlan_id'];
        $vlanname=$map_device_interface.'.'.$vlanid;
        $if_ip_add=json_decode($data['dhcp_info'],true)['dhcpcidr'];
        $netmask = explode('/',$if_ip_add)[1];
        $dhcp_hostsdir=DHCP_FILE_PATH.DIRECTORY_SEPARATOR.$net_id.DIRECTORY_SEPARATOR.$sub_id;
        $dhcp_pidfile=DHCP_FILE_PATH.DIRECTORY_SEPARATOR.'pid'.DIRECTORY_SEPARATOR.$net_id.DIRECTORY_SEPARATOR.$sub_id;
        $remove_dhcp_server = dhcplib()->remove_dhcp_server($dhcp_pidfile);
        handlefile()->Delfile($dhcp_pidfile);
        handlefile()->Deldir($dhcp_hostsdir);
        $remove_ip_add = iplib()->remove_ip_address($if_ip_add,$dhcp_inif,$dhcp_namespace);
        $del_if_out_ns = iplib()->del_if_out_ns($dhcp_inif,$dhcp_namespace);
        $remove_veth = iplib()->remove_nic($dhcp_outif);
        $remove_vlan = iplib()->remove_nic($vlanname);
        if($remove_dhcp_server && $remove_ip_add[0]===0 && $del_if_out_ns[0]===0 && $remove_veth[0] === 0 && $remove_vlan[0]===0){
            $network_add_config = config('config.network_add');
            $redis = new Redis($network_add_config);
            $key = $my_ip.'-'.$data['subname'];
            $msg = json_encode($data);
            $redis->del($key);
            return 1;
        }else{
            $result['remove_dhcp_server']= $remove_dhcp_server;
            $result['remove_ip_add'] = $remove_ip_add;
            $result['del_if_out_ns'] = $del_if_out_ns;
            $result['remove_veth'] = $remove_veth;
            $result['remove_vlan'] = $remove_vlan;
            return json_encode($result);
            
        }
    }
}
