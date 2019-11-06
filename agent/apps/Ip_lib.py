#coding=utf-8
import os
import sys
import subprocess
import signal
import pyroute2
import errno
from pyroute2 import IPRoute
from pyroute2 import netlink
from pyroute2.netlink import rtnl
from pyroute2 import NetlinkError
from pyroute2 import netns

BRIDGE_DIR = "/sys/class/net/"
def get_iplib(namespace) :
    if namespace :
        return pyroute2.NetNS(namespace, flags=0)
    else:
        return pyroute2.IPRoute()

def get_namespace(namespace):
    with pyroute2.NetNS(namespace, flags=0):
        pass

def _subprocess_setup():
    signal.signal(signal.SIGPIPE, signal.SIG_DFL)

def subprocess_popen(args,stdin=None, stdout=None, stderr=None, shell=False,env=None, preexec_fn=_subprocess_setup, close_fds=True):
    p=subprocess.Popen(args, shell=shell, stdin=stdin, stdout=stdout,stderr=stderr, preexec_fn=preexec_fn,close_fds=close_fds, env=env)
    return p.wait()

def _ip_link(cmd):
    cmd = ['ip','link'] +cmd
    return cmd

class Ip_lib(object):
    # virtual Network interface card function
    @classmethod
    def get_device_names(cls,namespace, **kwargs):
        with get_iplib(namespace) as ip:
            devices_attrs = [link['attrs'] for link in ip.get_links()]
            device_names = []
            for device_attrs in devices_attrs:
                for link_name in (link_attr[1] for link_attr in device_attrs if link_attr[0] == 'IFLA_IFNAME'):
                    device_names.append(link_name)
        return device_names

    @classmethod
    def interface_exists(cls,device,namespace=None) :
        try :
            with get_iplib(namespace) as ip:
                idx = ip.link_lookup(ifname=device)
                if len(idx) == 0 :
                    return False
                else:
                    return bool(idx[0])
        except IndexError:
            raise 

    @classmethod
    def delete_interface(cls,ifname,namespace=None,**kwargs):
        try:
            with get_iplib(namespace) as ip:
                return ip.link("del",index=ip.link_lookup(ifname=ifname)[0])[0]['header']['error']
        except OSError as e:
            if e.errno==errno.ENOENT:
                raise Exception("Interface does not exists.")
            raise

    @classmethod
    def create_tap(cls,ifname,namespace=None):
        with get_iplib(namespace) as ip:
            a = ip.link("add",ifname=ifname,kind="tuntap",mode="tap")[0]['header']['error']
            if a ==None:
                cls().set_up(ifname,namespace)[0]['header']['error']
            else:
                return False

    @classmethod
    def create_vlan_interface(cls,ifname,physical_interface,vlanid,namespace=None):
        ifname = str(ifname)
        physical_interface = str(physical_interface)
        vlanid = int(vlanid) 
        with get_iplib(namespace) as ip:
            a = ip.link("add",ifname=ifname,kind='vlan',link=ip.link_lookup(ifname=physical_interface)[0],vlan_id=vlanid)[0]['header']['error']
            if a ==None:
                return cls().set_up(ifname,namespace)[0]['header']['error']
            else:
                return False

    @classmethod
    def create_veth(cls,ifname1,ifname2,namespace1=None):
        return False

    # set interface status
    @classmethod
    def set_up(cls,interface,namespace=None):
        with get_iplib(namespace) as ip:
            return ip.link("set",index=ip.link_lookup(ifname=interface)[0],state="up")

    @classmethod
    def set_down(cls,interface,namespace=None):
        with get_iplib(namespace) as ip:
            return ip.link("set",index=ip.link_lookup(ifname=interface)[0],state="down")

    # BRIDGE function
    @classmethod
    def get_bridge_names(cls):
        return os.listdir(BRIDGE_DIR)

    @classmethod
    def get_bridge_interfaces(cls,bridge):
        return os.listdir(BRIDGE_DIR+bridge+'/brif')

    @classmethod
    def get_interface_bridge(cls,interface):
        try:
            path = os.readlink(BRIDGE_DIR+interface+'/brport/bridge')
        except OSError:
            return None
        else :
            name = path.rpartition('/')[-1]
            return name

    @classmethod
    def own_interface(cls,bridge,interface):
        return os.path.exists(BRIDGE_DIR+bridge+'/brif/'+interface)

    @classmethod
    def bridge_exists(cls,bridge):
        if not bridge:
            return False
        else:
            return os.path.exists(BRIDGE_DIR+bridge+'/bridge')

    @classmethod
    def addbr(cls,bridge,namespace=None):
        try:
            with get_iplib(namespace) as ip:
                a = ip.link("add",ifname=bridge,kind='bridge')[0]['header']['error']
                if a == None :
                    return cls().set_up(bridge)[0]['header']['error']
                else:
                    return False
        except NetlinkError as e:
            if e.code == errno.EEXIST:
                raise Exception("Interface already exists.")
            raise
        except OSError as e:
            if e.errno == errno.ENOENT:
                raise Exception("Interface already exists.")
            raise

    @classmethod
    def addif(cls,interface,bridge):
        cmd = ['set','dev',interface,'master',bridge]
        return subprocess_popen(_ip_link(cmd))
    @classmethod
    def delif(cls,interface):
        cmd = ['set','dev',interface,'nomaster']
        return subprocess_popen(_ip_link(cmd))
    @classmethod
    def disable_stp(cls,bridge):
        cmd =['set','dev',bridge,'type','bridge','stp_state',0]
        return subprocess_popen(_ip_link(cmd))

'''
namespace=None
ifname='vnet112'
interface='bond0'
vlanid='1111'
kind='vlan'
bridge='br112'
print(Ip_lib.addbr(bridge))
print(Ip_lib.bridge_exists(bridge))
print(Ip_lib.create_vlan_interface(ifname,kind,interface,vlanid))
print(Ip_lib.interface_exists(ifname,namespace))
print(Ip_lib.addif(ifname,bridge))
print(Ip_lib.get_interface_bridge(ifname))
print(Ip_lib.delete_interface(ifname))
print(Ip_lib.delete_interface(bridge))
'''
