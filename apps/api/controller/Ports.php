<?php
namespace api\controller;
use Vnet\db\Mysql;
use Vnet\db\Subinfo;

class Ports { 
    public function view($user='',$cluster='',$network='',$draw='',$start='',$length=''){
        //GET
        $method = strtolower(request()->get_method());
        if($method == 'get'){
            $request = request()->get();
        }
        $draw = isset($request['draw'])?$request['draw']:"1";
        $start = isset($request['start'])?$request['start']:"0";
        $length = isset($request['length'])?$request['length']:"50";
        $user = $request['user'];
        $cluster = $request['cluster'];
        $network = isset($request['network'])?$request['network']:"";
        if($network === "" ){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_ports";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_ports limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }elseif($network !== ""){
            $mysql = new Mysql();
            $sql = "select count(b.id) as row from vnet_networks a inner join vnet_ports b on a.network_id=b.network_id where a.network_name='$network'";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select b.network_id,b.subnet_id,b.portid,b.portmessage,a.network_name from ";
            $sql1 .= " vnet_networks a inner join vnet_ports b on a.network_id=b.network_id where a.network_name='$network' limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }
    }
    public function show(){
        return json_encode(-1);
    }
    public function dhcp(){
        //POST
        $method = strtolower(request()->get_method());
        $header = request()->get_header();
        if($method == 'post'){
            if($header['content-type'] =='application/json'){
                $request = json_decode(request()->get_rawbody(),true);
            }
        }
        $user = $request['user'];
        $cluster = $request['cluster'];
        $network = $request['network'];
        $subnet = $request['subnet'];
        $ipcount = $request['ipcount'];
        if(empty($user)||empty($cluster)||empty($network)||empty($subnet)||empty($ipcount)){
            return json_encode('{"status":"402","error":"Parameter error"}');
        }
        // 取得子网ID,DHCP range
        $subsql = "select a.network_id,a.brname,a.network_mode,b.subnet_id,b.vlan_id,b.dhcp_range from ";
        $subsql .= " vnet_networks a inner join vnet_subnets b on a.network_id=b.network_id and a.clusters=b.clusters where a.network_name='$network' and b.subnet_name='$subnet'";
        $mysql =  new Mysql();
        $subres = $mysql->query($subsql);
        if(count($subres['result'])!==1){
            // 这儿需要记录错误日志,需要log类
            $data['status'] = 402;
            $data['error'] = 'The Number of networks or subnets error';
            return json_encode($data);
        }
        $netid = $subres['result']['0']['network_id'];
        $brname = $subres['result']['0']['brname'];
        $mode = $subres['result']['0']['network_mode'];
        $subid = $subres['result']['0']['subnet_id'];
        $dhcprange = explode(',',$subres['result']['0']['dhcp_range']);
        $dhcpstart = $dhcprange['0'];
        $vlanid=$subres['result']['0']['vlan_id'];
        // 查找子网IP池可用IP地址
        $subinfo = new Subinfo();
        $useableipcountsql = "select count(id) as row from $subid where stat='-1' and inet_aton(ip)>inet_aton('$dhcpstart')";
        $useableipcountres = $subinfo->query($useableipcountsql);
        if($useableipcountres['result']['0']['row'] < $ipcount){
            // 这儿需要记录错误日志,需要log类
            $data['status'] = 402;
            $data['error'] = 'The Number of ip address in subnets error';
            return json_encode($data);
        }
        // 锁子网IP池，为DHCP分配IP地址，生成端口信息
        $mysql->begin();
        $subinfo->begin();
        $assignipsql = "select id,ip,mac from $subid where stat='-1' and inet_aton(ip)>inet_aton('$dhcpstart') limit 0,$ipcount for update";
        $assignipres = $subinfo->query($assignipsql);
        $vmvalue = array();
        foreach($assignipres['result'] as $value){
            $ip_id = $value['id'];
            $ip_addr = $value['ip'];
            $mac_addr = $value['mac'];
            $portid = substr(sha1(md5(uniqid(microtime(true),true))),0,16);
            $portname = "vnet-".substr(sha1(md5(uniqid(microtime(true),true))),0,8);
            $portmessage ='{"portname":"'.$portname.'","ip":"'.$ip_addr.'","mac":"'.$mac_addr.'","how_use":"VM"}';
            $portvalues = "('".$netid."','".$subid."','".$portid."','".$portmessage."')";
            $r_data['brname'] = $brname;
            $r_data['portid'] = $portid;
            $r_data['portname'] = $portname;
            $r_data['ip'] = $ip_addr;
            $r_data['mac'] = $mac_addr;
            $r_data['vlanid'] = $vlanid;
            // 修改subnet池IP表IP地址的状态为2(allocated),新增信息到port表
            $useablestatsql = "update $subid set stat='2' where id='$ip_id'";
            $newportsql = "insert into vnet_ports(network_id,subnet_id,portid,portmessage)values".$portvalues;
            $useablestatres = $subinfo->query($useablestatsql);
            $newportres = $mysql->query($newportsql);
            if($useablestatres['affected_rows']===1 && $newportres['result']){
                $r_data['id'] = $newportres['insert_id'];
                $add_port_result[]=$newportres['result'];
            }else{
                $mysql->rollback();
                $subinfo->rollback();
                $rdata['status']=402;
                $rdata['error'] = 'Change ip address in subnet table error or add new port into ports table error';
                return json_encode($rdata);
            }
            $vmvalue[] = $r_data;            
        }
        if(count(array_unique($add_port_result))===1){
            $mysql->commit();
            $subinfo->commit();
            return json_encode($vmvalue);
        }else{
            $mysql->rollback();
            $subinfo->rollback();
            // 这儿需要记录错误日志,需要log类
            $rdata['status']=402;
            $rdata['error'] = 'Assign dhcp ip address error';
            return json_encode($rdata);
        }
    }
    public function put(){
        $server = request()->getserver();
        $header = request()->getheader();
        if($server['request_method'] == 'POST' ||$server['request_method'] == 'post'){
            if($header['content-type'] =='application/json'){
                $request = json_decode(request()->rawbody(),true);
            }else if($header['content-type'] =='application/x-www-form-urlencoded'){
                $request = request()->post();
            }
        }
        $user = $request['user'];
        $cluster = $request['cluster'];
        $network = $request['network'];
        $subnet = $request['subnet'];
        $port = $request['portid'];
        $ipaddr = $request['ip'];
        
        if(empty($user)||empty($cluster)||empty($network)||empty($subnet)||empty($port)||is_array($user)||is_array($cluster)||is_array($network)||is_array($subnet)||is_array($port)||is_array($ipaddr)){
            return json_encode(-1);
        }else{
            $datasql = "select a.network_id,a.subnet_id,a.portmessage from ports a inner join networks b on a.network_id=b.network_id inner join subnets c on b.owner_cluster=c.owner_cluster and a.subnet_id=c.subnet_id where b.network_name='$network' and c.subnet_name='$subnet' and a.portid='$port'";
            $mysql = new Mysql();
            $datares = $mysql->query($datasql);
            if(count($datares['result'])===1 && json_decode($datares['result']['0']['portmessage'],true)['ip'] && json_decode($datares['result']['0']['portmessage'],true)['how_use'] =='FIX'){
                $netid = $datares['result']['0']['network_id'];
                $subid = $datares['result']['0']['subnet_id'];
                $portname = json_decode($datares['result']['0']['portmessage'],true)['portname'];
                $oldip = json_decode($datares['result']['0']['portmessage'],true)['ip'];
                $oldmac = json_decode($datares['result']['0']['portmessage'],true)['mac'];
                $newmacsql = "select mac from $subid where ip='$ipaddr' and stat='-1'";
                $mysql->begin();
                $newmacres = $mysql->query($newmacsql);
                if(!$newmacres['result']){
                    $mysql->rollback();
                    return json_encode(-1);
                }
                $portmessage ='{"portname":"'.$portname.'","ip":"'.$ipaddr.'","mac":"'.$newmacres['result']['0']['mac'].'","how_use":"FIX"}';
                $m_oldipsql = "update $subid set stat='-1' where ip='$oldip' and mac='$oldmac'";
                $n_newipsql = "update $subid set stat='1' where ip='$ipaddr'";
                $m_portsql = "update ports set portmessage='$portmessage' where portid='$port' and network_id='$netid' and subnet_id='$subid'";
                $m_oldipres=$mysql->query($m_oldipsql);
                $n_newipres=$mysql->query($n_newipsql);
                $m_portres=$mysql->query($m_portsql);
                if($m_oldipres['affected_rows']=='1' && $n_newipres['affected_rows']=='1' && $m_portres['affected_rows']=='1'){
                    $mysql->commit();
                    return json_encode(1);
                }else{
                    $mysql->rollback();
                    return json_encode(-1);
                }
            }elseif(count($datares['result'])===1 && !json_decode($datares['result']['0']['portmessage'],true)['ip'] && json_decode($datares['result']['0']['portmessage'],true)['how_use'] =='FIX'){
                $netid = $datares['result']['0']['network_id'];
                $subid = $datares['result']['0']['subnet_id'];
                $portname = json_decode($datares['result']['0']['portmessage'],true)['portname'];
                $newmacsql = "select mac from $subid where ip='$ipaddr' and stat='-1'";
                $mysql->begin();
                $newmacres = $mysql->query($newmacsql);
                if(!$newmacres['result']){
                    return json_encode(-1);
                }
                $portmessage ='{"portname":"'.$portname.'","ip":"'.$ipaddr.'","mac":"'.$newmacres['result']['0']['mac'].'","how_use":"FIX"}';
                $n_newipsql = "update $subid set stat='1' where ip='$ipaddr'";
                $m_portsql = "update ports set portmessage='$portmessage' where portid='$port' and network_id='$netid' and subnet_id='$subid'";
                $n_newipres=$mysql->query($n_newipsql);
                $m_portres=$mysql->query($m_portsql);
                if($n_newipres['affected_rows']=='1' && $m_portres['affected_rows']=='1'){
                    $mysql->commit();
                    return json_encode(1);
                }else{
                    $mysql->rollback();
                    return json_encode(-1);
                }
                
            }else{
                return json_encode(-2);
            }
        }
    }
    public function del($id=''){
        //DELETE
        $method = strtolower(request()->get_method());
        $header = request()->get_header();
        if($method != 'delete'){
            return json_encode('{"status":"405","error":"Method Not Allowed"}');
        }
        if($header['content-type'] =='application/json'){
            $request = json_decode(request()->get_rawbody(),true);
        }
        $id=$id[0];
        $user = $request['user'];
        $cluster = $request['cluster'];
        $network = $request['network'];
        $subnet = $request['subnet'];
        if(empty($user)||empty($cluster)||empty($network)||empty($subnet)||empty($id)){
            return json_encode('{"status":"402","error":"Parameter error"}');
        }
        $mysql = new Mysql();
        $subsql = "select b.subnet_id from vnet_networks a inner join vnet_subnets b on a.network_id=b.network_id and a.clusters=b.clusters ";
        $subsql .= " where a.network_name='$network' and b.subnet_name='$subnet'";
        $subres = $mysql->query($subsql);
        if(!$subres['result']){
            $data['status'] = 402;
            $data['error'] = 'Network or subnet error';
            return json_encode($data);
        }
        $subid = $subres['result']['0']['subnet_id'];
        $portinfosql = "select portmessage from vnet_ports where id='$id'";
        $portinfores = $mysql->query($portinfosql);
        if($portinfores['result']){
            $portmessage = json_decode($portinfores['result']['0']['portmessage'],true);
            $ip = $portmessage['ip'];
            $mac = $portmessage['mac'];
        }
        $delportssql = "delete from vnet_ports where id='$id'";
        $subinfo = new Subinfo();
        $updatesubtabsql = "update $subid set stat='-1' where ip='$ip' and mac='$mac'";
        $updatesubtabres = $subinfo->query($updatesubtabsql);
        $delportsres = $mysql->query($delportssql);
        if($delportsres['result']&&$updatesubtabres['result']){
            return json_encode('{"status":"200"}');
        }else{
            $data['status'] = 402;
            $data['error'] = 'Delete port or Ip address error';
            return json_encode($data);
        }
    }
}
