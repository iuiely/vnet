<?php
namespace Vnet;

Trait Singleton{
    //存放实例
    private static $_instance = null;
    //私有化克隆方法
    private function __clone(){}
    private function __wakeup(){}
    private function __construct(){}
    //公有化获取实例方法
    public static function getInstance(){
        $class = __CLASS__;
        if (!(self::$_instance instanceof $class)){
            self::$_instance = new $class();
        }
        return self::$_instance;
    }
}
