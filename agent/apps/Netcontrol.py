import os,sys,time,json
import pdb
from Ip_lib import *
from frame.lib.Config import *

class Netcontrol(object):
    def handle_data(self,msg):
        data = dict(json.loads(msg))
        for keys in data.keys():
            if keys == 'network' or keys == 'subnet' :
                key = keys
                break
            else:
                key="" 
        my_ip = Config.get('agent.default.my_ip')
        vlan_config = Config.get('agent.vlan')
        physical_interface = vlan_config['vlan_map_interface']
        try:
            if key == 'network' :
                vlanid=data['vlanid']
	        vlan_ifname = physical_interface+'.'+vlanid
                bridge = data['brname']
                if data[key] == 'add' and  my_ip == data['host'] and data['mode'] == 'vlan' and vlan_config['enable_vlan'] == '1' and vlan_config['vlan_drive'] == 'bridge':
                    if Ip_lib.bridge_exists(bridge) == False:
                        Ip_lib.addbr(bridge)
                    if Ip_lib.interface_exists(vlan_ifname) == False:
                        Ip_lib.create_vlan_interface(vlan_ifname,physical_interface,vlanid)
                    if Ip_lib.own_interface(bridge,vlan_ifname) == False:
                        Ip_lib.addif(vlan_ifname,bridge)
                elif data[key] == 'del' and  my_ip == data['host'] and vlan_config['vlan_drive'] == 'bridge' :
                    if Ip_lib.interface_exists(vlan_ifname) == True:
                        Ip_lib.delete_interface(vlan_ifname)
                    if Ip_lib.bridge_exists(bridge) == True:
                        Ip_lib.delete_interface(bridge)
                else :
                    pass
            elif key == 'subnet' :
                vlanid=data['vlanid']
	        vlan_ifname = physical_interface+'.'+vlanid
                bridge = data['brname']
                if data[key] == 'add' and  my_ip == data['host'] and data['mode'] == 'vlan' and vlan_config['enable_vlan'] == '1' and vlan_config['vlan_drive'] == 'bridge':
                    if Ip_lib.bridge_exists(bridge) == True and Ip_lib.interface_exists(vlan_ifname) == False:
                        Ip_lib.create_vlan_interface(vlan_ifname,physical_interface,vlanid)
                        Ip_lib.addif(vlan_ifname,bridge)
                    else:
                        pass
                elif data[key] == 'del' and  my_ip == data['host'] and vlan_config['vlan_drive'] == 'bridge' :
                    if Ip_lib.interface_exists(vlan_ifname) == True:
                        Ip_lib.delete_interface(vlan_ifname)
                else:
                    pass
        except ValueError as e:
            print('ValueError:',e)
        #pdb.set_trace()
