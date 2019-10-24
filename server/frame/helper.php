<?php

function get_instance($class){
    return ($class)::getInstance();
}


if (!function_exists('config')) {
    function config($name, $value = null)    {
         return get_instance('\Vnet\Config')->get($name,$value);
    }
}

if(!function_exists('request')){
    function request(){
        return get_instance('\Vnet\web\HttpRequest');
    }
}

if(!function_exists('handlefile')){
    function handlefile(){
        return get_instance('\Vnet\HandleFilesystem');
    }
}

if(!function_exists('iplib')){
    function iplib(){
        return get_instance('\Vnet\console\Ip_lib');
    }
}

if(!function_exists('dhcplib')){
    function dhcplib(){
        return get_instance('\Vnet\console\Dhcp_lib');
    }
}
