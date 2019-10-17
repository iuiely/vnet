<?php
namespace Vnet;
class Loader{
    protected static $classMap = [];
    protected static $namespaces = [];
    protected static $ext_namespaces = [];

    public static function autoload($class){
        // find class file and include file 
        if ($file = self::findFile($class)) {
                include $file;
                return true;
            }
        }
    private static function findFile($class){
        if (!empty(self::$classMap[$class])) {
            return self::$classMap[$class];
        }
        $numberBeforeFirstSlash=strpos($class,'\\');
        if(false !== $numberBeforeFirstSlash){
            $contentBeforeFirstSlash = strstr($class,'\\',true);
            $appnamespace = [];
            $set = require LIB_CONFIG_FILE;
            $keys=array_keys($set['default']['namespace']);
            for($i=0;$i<count($keys);$i++){
                $appnamespace[]=$set['default']['namespace'][$keys[$i]];
            }
            if(in_array($contentBeforeFirstSlash,array('Vnet'))||is_dir(LIB_PATH . $contentBeforeFirstSlash)){
                $dir = LIB_PATH;
            }elseif(in_array($contentBeforeFirstSlash,$appnamespace)){
                $dir = $set['default']['app_path'].DIRECTORY_SEPARATOR.$contentBeforeFirstSlash;
            }else{
                $dir = EXT_PATH.$contentBeforeFirstSlash;
            }
            $class_array = array_filter(explode('\\',$class));
            array_shift($class_array);
            $logicalfile = implode(DIRECTORY_SEPARATOR,$class_array).'.php';
            if(isset(self::$namespaces[$contentBeforeFirstSlash])){
                $dir = $dir;
                if(is_file($file = $dir.$logicalfile)){
                    self::$classMap[$class] = $file;
                    return $file;
                }
            }else{
                if(is_file($file = $dir.DIRECTORY_SEPARATOR.$logicalfile)){
                    self::$classMap[$class] = $file;
                    return $file;
                }
            }
            return self::$classMap[$class] = false;
        }
    }
    public static function register(){
        spl_autoload_register('\\Vnet\\Loader::autoload',true , true );
        self::addNamespace ('Vnet',__DIR__.'/');
    }
    public static function addClassMap($class, $map = ''){
        if (is_array($class)) {
            self::$classMap = array_merge(self::$classMap, $class);
        } else {
            self::$classMap[$class] = $map;
        }
    }
    public static function addNamespace($namespace,$path=''){
        self::$namespaces[$namespace] = rtrim($path,'/').DIRECTORY_SEPARATOR;
    }
}
