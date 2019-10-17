<?php
namespace api\controller;
use Vnet\db\Mysql;
use Vnet\rmq\RabbitMqexecute;
use Vnet\db\Subinfo;

class Subnets { 
    public function view($user='',$cluster='',$networkname=''){
    // GET
        $method = strtolower(request()->get_method());
        if($method == 'get'){
            $request = request()->get();
        }
        if(!empty($request)){
            $user = $request['user'];
            $cluster = $request['cluster'];
            $networkname = $request['networkname'];
        }else{
            return json_encode('{"406":"Not Acceptable"}');
        }
        $netidsql = "select network_id from vnet_networks where network_name='$networkname'";
        $mysql = new Mysql();
        $res = $mysql->query($netidsql);
        if($res['result']){
            $netid = $res['result']['0']['network_id'];
        }else{
            $data['status'] = 402;
            $data['sql'] = $netidsql;
            $data['error'] = $res['error'];
            return json_encode($data);
        }
        $subsql = "select * from vnet_subnets where network_id='$netid' and owner_user='$user' and owner_cluster='$cluster'";
        $result = $mysql->query($subsql);
        if($result['result']){
            return json_encode($result);
        }else{
            $data['status'] = 402;
            $data['sql'] = $subsql;
            $data['error'] =$result['error'];
            return json_encode($data);
        }
    }
    public function show($id){
    //GET
        $method = strtolower(request()->get_method());
        if($method == 'get'){
            $request = request()->get();
        }        
        $id = $id[0];
        $mysql = new Mysql();
        $sql = "select a.network_id,a.network_name,a.network_mode,b.* from vnet_networks a inner join vnet_subnets b on a.network_id=b.network_id where b.id='$id'";
        $result = $mysql->query($sql);
        return json_encode($result['result']);
    }
    public function add(){
    // POST
        $method = strtolower(request()->get_method());
        $header = request()->get_header();
        if($method == 'post'){
            if($header['content-type'] =='application/json'){
                $request = json_decode(request()->get_rawbody(),true);
            }
        }
        date_default_timezone_set("Asia/Shanghai");
        $netname = $request['netname'];
        $cluster = $request['cluster'];
        $user = $request['user'];
        $subname = $request['subname'];
        $subid = 's'.substr(hash("sha256",sha1(md5(uniqid(microtime(true),true)))),0,19);
        $ipversion = $request['ipversion'];
        $vlanid = isset($request['vlanid'])?$request['vlanid'] : 0 ;
        $ipcidr = $request['ipcidr'];
        $gateway = $request['gateway'];
        $dhcpstat = isset($request['dhcpstat'])?$request['dhcpstat'] : -1;
        $dhcprange = $request['dhcprange'];
        
        if(empty($netname)||empty($cluster)||empty($user)){
            return json_encode('{"406":"Not Acceptable"}');
        }
        $netsql = "select network_id,groups,network_mode,brname,dhcpnamespace,routenamespace from vnet_networks where network_name='$netname' and clusters='$cluster' and users='$user'";
        $mysql = new Mysql();
        $res = $mysql->query($netsql);
        if(!$res['result']){
            // need to log the error
            $data['status'] = 402;
            $data['error'] = 'The network does not exist';
            return json_encode($data);
        }
        $netid = $res['result']['0']['network_id'];
        $group = $res['result']['0']['groups'];
        $swname = $res['result']['0']['brname'];
        $mode = $res['result']['0']['network_mode'];
        $dhcp_namespace = $res['result']['0']['dhcpnamespace'];
        $route_namespace = $res['result']['0']['routenamespace'];
        $vlan_model = config('config.network.enable_vlan');
        $vxlan_model = config('config.network.enable_vxlan');
        if(empty($subname)||empty($subid)||empty($ipversion)||empty($vlanid)||empty($ipcidr)||empty($gateway)||empty($netid)||empty($dhcpstat)||empty($group)){
            return json_encode('{"status":"402","error":"Parameter error"}');
        }
        if($mode =='vlan' && $vlan_model){
            $checksubsql = "select count(id) as row from vnet_subnets where subnet_name='$subname' and clusters='$cluster' and groups='$group' or subnet_id='$subid'";
            $checksubres = $mysql->query($checksubsql);
            $count = $checksubres['result']['0']['row'];
            if($count!=='0'){
                // need to log the error
                $data['status'] = 402;
                $data['error'] = 'The subnet exist';
                return json_encode($data);
            }
            $sql = "CREATE TABLE $subid(id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,ip VARCHAR(16) NOT NULL,mask VARCHAR(16) NOT NULL,";
            $sql .= "gateway VARCHAR(16) NOT NULL,mac VARCHAR(19) NOT NULL,stat tinyint(4) NOT NULL,UNIQUE (ip))ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $subinfo = new Subinfo();
            $create_sub_table = $subinfo->query($sql);
            if($create_sub_table['result'] !== true){
                $create_sub_table = $subinfo->query($sql);
                if($create_sub_table['result'] !== true){
                    $create_sub_table = $subinfo->query($sql);
                }else{
                    // 这儿需要记录错误日志,需要log类
                    $data['status'] =402;
                    $data['sql'] = $sql;
                    $data['error'] = 'Create subnet table error';
                    return json_encode($data);
                }
            }
            $nodesql = "select nodes,dhcp,router from vnet_nodes where uses='server'";
            $noderes = $mysql->query($nodesql);
            $dhcpstatus=[];
            $routerstatus=[];
            foreach($noderes['result'] as $value){
                $dhcpstatus[] = $value['dhcp'];
                $routerstatus[]=$value['router'];
            }
            if(count(array_unique($dhcpstatus))!==1 && count(array_unique($routerstatus))!==1){
                $data['status'] = 402;
                $data['error'] = 'DHCP status error or ROUTER status error or Configuration files are inconsistent';
                return json_encode($data); // Configuration files are inconsistent
            }
            // 生成子网IP地址列表
            $ip_list = $subinfo->iparray($ipcidr);
            $key = array_search("$gateway",array_column($ip_list,'0'));
            if($key !== false ){
                array_splice($ip_list,$key,1);
            }
            $ip_value="";
            $ipcount = count($ip_list);
            $num = count($ip_list)-1; 
            for($i=0;$i<$num;$i++){
                $mac = "54:".implode(':',str_split(substr(sha1(md5(uniqid(microtime(true),true))),0,10),2));
                $ip_value .= "('".$ip_list[$i][0]."','".$ip_list[$i][1]."','".$gateway."','".$mac."','-1'),";
            }
            $last_mac = "54:".implode(':',str_split(substr(sha1(md5(uniqid(microtime(true),true))),0,10),2));
            $ip_value .= "('".end($ip_list)[0]."','".end($ip_list)[1]."','".$gateway."','".$last_mac."','-1')";
            // If enable DHCP server,then execute
            $dhcpconfig = config('config.network.dhcp');
            if($dhcpstat == 1  && count(explode(',',$dhcprange)) ===2 && $dhcpconfig){
                //多个DHCP服务器，取得多个DHCP IP地址，并标记用途
                $dhcp_amount = count($dhcpstatus);
                $dhcpipaddress =[];
                $t_aaa=[];
                $dhcp_info ="";
                $portmsg="";
                $rmq = new RabbitMqexecute();
                foreach($noderes['result'] as $value){
                    $my_ip=$value['nodes'];
                    for($p=0;$p<$dhcp_amount;$p++){
                        $assign_dhcp_ip = long2ip(ip2long(explode(',',$dhcprange)['0'])+$p);
                        $out_if = 'dhcp-'.substr(hash("sha512",sha1(md5(uniqid(microtime(true),true)))),10,9);
                        $out_mac = '28:'.implode(':',str_split(substr(sha1(md5(uniqid(microtime(true),true))),0,10),2));
                        $in_if = 'tap-'.substr(hash("sha512",sha1(md5(uniqid(microtime(true),true)))),15,10);
                        $in_mac = '28:'.implode(':',str_split(substr(sha1(md5(uniqid(microtime(true),true))),20,10),2));
                        $dhcpportid = substr(sha1(md5(uniqid(microtime(true),true))),0,16);
                        $namespaceportid = substr(sha1(md5(uniqid(microtime(true),true))),0,16);
                        if(!in_array($my_ip,$t_aaa) && !in_array($assign_dhcp_ip,$dhcpipaddress)){
                            $dhcpipaddress[] = $assign_dhcp_ip;
                            $t_aaa[]=$my_ip;
                            for($i=0;$i<$ipcount;$i++){
                                if($dhcpipaddress[$p] == $ip_list[$i][0]){
                                    $dhcp_if = '{"portname":"'.$out_if.'","ip":"'.$assign_dhcp_ip.'","mac":"'.$out_mac.'","how_use":"DHCP"}';
                                    $namespace_if = '{"portname":"'.$in_if.'","ip":"'.$assign_dhcp_ip.'","mac":"'.$in_mac.'","how_use":"DHCP"}';
                                    $portmsg.= "('".$netid."','".$subid."','".$dhcpportid."','".$dhcp_if."'),('".$netid."','".$subid."','".$namespaceportid."','".$namespace_if."')";
                                    $dhcp_msg= '{"out_if":"'.$out_if.'","out_mac":"'.$out_mac.'","in_if":"'.$in_if.'","in_mac":"'.$in_mac;
                                    $dhcp_msg.= '","dhcpcidr":"'.$ip_list[$i][0].'/'.$ip_list[$i][1].'","my_ip":"'.$my_ip.'","how_use":"DHCP"}';
                                    $dhcp_info.="('".$netid."','".$subid."','".$dhcp_namespace."','".$dhcp_msg."')";
                                    $rmq_msg['subnet'] = 'add'; $rmq_msg['net_id'] = $netid;$rmq_msg['brname'] = $swname; $rmq_msg['network_mode'] = $mode; $rmq_msg['dhcp_namespace'] = $dhcp_namespace;
                                    $rmq_msg['sub_id'] = $subid; $rmq_msg['subname'] = $subname; $rmq_msg['vlan_id'] = $vlanid; $rmq_msg['gateway'] = $gateway; $rmq_msg['enable_dhcp'] = $dhcpstat;
                                    $rmq_msg['dhcp_range'] = $dhcprange;
                                    $rmq_msg['dhcp_info'] = '{"out_if":"'.$out_if.'","out_mac":"'.$out_mac.'","in_if":"'.$in_if.'","in_mac":"'.$in_mac.'","dhcpcidr":"'.$ip_list[$i][0].'/'.$ip_list[$i][1].'","my_ip":"'.$my_ip.'","how_use":"DHCP"}';
                                }
                            }
                        }
                    }
                    $msg = json_encode($rmq_msg);
                    $rmq->sendmsg($msg, 'vnet',$value['nodes'],$value['nodes'].'server');
                }
                //设置已分配的DHCP服务器IP地址为使用状态    
                $splitipvalue = explode('),',$ip_value);
                for($i=0;$i<count($splitipvalue);$i++){
                    for($l=0;$l<$dhcp_amount;$l++){ 
                        $dhcpip = $dhcpipaddress[$l];
                        if(in_array("('".$dhcpip."'",explode(',',$splitipvalue[$i]))){
                            $ipdhcp = explode(',',$splitipvalue[$i]);
                            $ipdhcp[4]="'1'";
                            $newdhcpip="";
                            for ($j=0;$j<count($ipdhcp)-1;$j++){
                                $newdhcpip .= $ipdhcp[$j].',';
                            }
                            $newdhcpip.=end($ipdhcp);
                            $splitipvalue[$i] = $newdhcpip;
                        }
                    }
                }
                $newiplist = "";
                for ($s=0;$s<count($splitipvalue)-1;$s++){
                   $newiplist .= $splitipvalue[$s].'),';
                }
                $newiplist .= end($splitipvalue);
                $in_iplist_sql = "insert into ".$subid."(ip,mask,gateway,mac,stat)values".$newiplist;
                $in_port_sql = "insert into vnet_ports (network_id,subnet_id,portid,portmessage)value".$portmsg;
                $in_sub_sql = "insert into vnet_subnets(network_id,subnet_name,subnet_id,clusters,users,groups,ip_version,vlan_id,ip_cidr,gateway,enable_dhcp,dhcp_range)";
                $in_sub_sql .= "value('$netid','$subname','$subid','$cluster','$user','$group','$ipversion','$vlanid','$ipcidr','$gateway','$dhcpstat','$dhcprange')";
                $in_vdhcp_sql = "insert into vnet_dhcps (network_id,subnet_id,dhcp_namespace,dhcp_msg)value".$dhcp_info;
                $mysql->begin();
                $in_iplist_res = $subinfo->query($in_iplist_sql);
                $in_sub_res = $mysql->query($in_sub_sql);
                $in_port_res = $mysql->query($in_port_sql);
                $in_vdhcp_res = $mysql->query($in_vdhcp_sql);
                if($in_iplist_res['result'] && $in_sub_res['result'] && $in_port_res['result'] && $in_vdhcp_res['result']){
                    $mysql->commit();
                    return json_encode('{"status":"200"}');
                }else{
                    $mysql->rollback();
                    $data['status'] = 402;
                    $data['in_port_sql'] = $in_port_sql;
                    $data['in_vdhcp_sql'] = $in_vdhcp_sql;
                    $data['error'] = 'Create subnet ip error or insert ports error,or insert subnet information error or insert dhcp information error';
                    return json_encode($data);
                }
            }else{
                $data['status'] = 402;
                $data['error'] = 'DHCP file status error';
                return json_encode($data);
            }
        }
    }
    public function put(){
        return -1;
    }
    public function del($id){
        //DELETE
        $method = strtolower(request()->get_method());
        $header = request()->get_header();
        if($method != 'delete'){
            return json_encode('{"405":"Method Not Allowed"}');
        }
        if($header['content-type'] =='application/json'){
            $request = json_decode(request()->get_rawbody(),true);
        }elseif($header['content-type'] =='application/x-www-form-urlencoded'){
            $request = request()->post();
        }
        $id = $id[0];
        $user = $request['user'];
        if(empty($id)||empty($user)){
            $data['status'] = 402;
            $data['error'] = 'Parameter error';
            return json_encode($data);
        }
        $sql = "select count(id) as row from vnet_subnets where users='$user' and id='$id'";
        $mysql = new Mysql();
        $result = $mysql->query($sql);
        if($result['result']['0']['row'] === '1'){
            //  取得将要删除子网的基本信息并处理，如果子网基本信息不是唯一，退出返回错误 -2
            $subsql = "select a.subnet_id,a.vlan_id,b.network_id,b.network_mode,b.brname,b.dhcpnamespace from "; 
            $subsql .= " vnet_subnets a inner join vnet_networks b on a.network_id=b.network_id and a.groups=b.groups inner join vnet_dhcps c on a.network_id=c.network_id where a.id='$id'";
            $subres = $mysql->query($subsql);
            foreach($subres['result'] as $value){
                $subid[] = $value['subnet_id'];
                $netid[] = $value['network_id'];
                $vlanid[] = $value['vlan_id'];
                $swname[] = $value['brname'];
                $mode[] = $value['network_mode'];
                $dhcp_namespace[] = $value['dhcpnamespace'];
            }
            if(count(array_unique($subid))===1 && count(array_unique($netid))===1 && count(array_unique($vlanid))===1 && count(array_unique($swname))===1 && count(array_unique($mode))===1 && count(array_unique($dhcp_namespace))===1){
                $subid = implode(array_unique($subid));
                $netid = implode(array_unique($netid));
                $vlanid = implode(array_unique($vlanid));
                $swname = implode(array_unique($swname));
                $mode = implode(array_unique($mode));
                $dhcp_namespace = implode(array_unique($dhcp_namespace));
            }else{
                $data['status'] = 402;
                $data['error'] = 'Subnet data error';
                return json_encode($data);
            }
            // get subid表中IP地址的使用情况
            $subinfo = new Subinfo();
            $ip_used_sql = "select ip,mask from $subid where 1=1 and stat>='1'";
            $ip_used_res = $subinfo->query($ip_used_sql);
            // 取得ports表中port端口的使用情况
            $ports_used_sql = "select portmessage from vnet_ports where network_id='$netid' and subnet_id='$subid'";
            $ports_used_res = $mysql->query($ports_used_sql);
            // 取得VDHCPS下的子网列表
            $vdhcp_sql = "select dhcp_msg from vnet_dhcps where network_id='$netid' and subnet_id='$subid'";
            $vdhcp_res = $mysql->query($vdhcp_sql);
            foreach($vdhcp_res['result'] as $value){
                $dhcp_msg[] = $value['dhcp_msg'];
            }
            // 对比已分配的IP地址使用情况
            if($ip_used_res['result']){
                $list_ip = array();
                $used_ip = array();
                foreach($ip_used_res['result'] as $ip){
                    $list_ip[] = $ip['ip'];
                    $mask = $ip['mask'];
                    foreach($ports_used_res['result'] as $key=>$msg){
                        $msg_res = json_decode($msg['portmessage'],true);
                        if($msg_res && in_array($ip['ip'],$msg_res)&& $msg_res['how_use'] =='DHCP'){
                            $used_ip[] = $ip['ip'];
                        }
                    }
                }
                $used_ip = array_flip($used_ip);
                $used_ip = array_flip($used_ip);
                $used_ip = array_values($used_ip);
            }
            if(strcasecmp(json_encode($list_ip),json_encode($used_ip)) !== 0){
                $data['status'] = 402;
                $data['error'] = 'Server ip address error';
            }
            //  如果子网已分配的IP地址，属于DHCP服务器，发送删除设备的信息到消息队列
            $rmq = new RabbitMqexecute();
            foreach($dhcp_msg as $node_msg){
                $rmq_msg['subnet'] = 'del';
                $rmq_msg['net_id'] = $netid;
                $rmq_msg['brname'] = $swname;
                $rmq_msg['network_mode'] = $mode;
                $rmq_msg['dhcp_namespace'] = $dhcp_namespace;
                $rmq_msg['sub_id'] = $subid;
                $rmq_msg['vlan_id'] = $vlanid;
                $rmq_msg['dhcp_info'] = $node_msg;
                $msg = json_encode($rmq_msg);
                $rmq->sendmsg($msg, 'vnet',json_decode($node_msg,true)['my_ip'],json_decode($node_msg,true)['my_ip'].'server');
            }
            // 从SUBNET表中删除 ID 行 数据,从ports表中删除SUBNET id的行数,据删除等于subnet_id号的子网IP地址表,delete the subnet row in the vdhcps 
            $sub_del_sql = "delete from vnet_subnets where users='$user' and id='$id' and subnet_id='$subid'";
            $port_del_sql = "delete from vnet_ports where subnet_id='$subid'";
            $vdhcp_del_sql = "delete from vnet_dhcps where network_id='$netid' and subnet_id='$subid'";
            $drop_tab = "drop table $subid";
            $mysql->begin();
            $subdelres = $mysql->query($sub_del_sql);
            $portdelres = $mysql->query($port_del_sql);
            $vdhcp_del_res = $mysql->query($vdhcp_del_sql);
            if($subdelres['affected_rows']=='1' && $portdelres['result'] && $vdhcp_del_res['result']){
                $subinfo->query($drop_tab);
                $mysql->commit();
                return json_encode('{"status":"200"}');
            }else{
                // 这儿需要记录错误日志，需要log类
                $mysql->rollback();
                $data['status'] = 402;
                $data['error'] = 'Delete subnet error or Delete port error or delete dhcp error';
                return json_encode($data);
            }
        }else{
            return json_encode(-2);
        }
    }
}
