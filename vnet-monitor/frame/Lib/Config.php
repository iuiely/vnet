<?php
namespace Vnet;
use Vnet\Singleton;
class Config{
    Use Singleton;
    private static $config = [];
    private static $file;
    private static $name;

    public function get($name, $default = null){
        if(empty($name)){
            return NULL;
        }
        self::$name = array_filter(explode('.',strtolower($name)));

        $count = count(self::$name);
        if($count === 1){
            self::$file = strtolower($name);
            if(empty(self::$config[self::$file])){
                if(!is_file(APP_CONFIG_PATH.self::$file.'.ini')){
                    return null;
                }
                self::$config[self::$file] = parse_ini_file(APP_CONFIG_PATH.self::$file.'.ini',true);
            }
            return self::$config[self::$file];
        }else{
            self::$file =  strtolower(array_shift(self::$name));
            if(empty(self::$config[self::$file])){
                if(!is_file(APP_CONFIG_PATH.self::$file.'.ini')){
                    return null;
                }
                self::$config[self::$file] = parse_ini_file(APP_CONFIG_PATH.self::$file.'.ini',true);
            }

            $config = self::$config[self::$file];
            while(self::$name){
                $key = array_shift(self::$name);
                if(!isset($config[$key])){
                    $config = $default;
                    break;
                }
                $config = $config[$key];
            }
            return $config;
        }
    }
}
