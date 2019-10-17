<?php
namespace Vnet\web;
use Vnet\Singleton;
use Vnet\Config;

class HttpRoute{
    Use Singleton;    
    private static $module;
    private static $controller;
    private static $action;
    private static $args;
    public static $debug = true;
    private static $config;
    private static $request;
    private static $response;
        
    public function http_route($request){
        //Get the request uri
        $conf = include dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'config/set.php';
        $request_mode = $conf['default']['mode'];
        if($request_mode == "api"){
            $route = include dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'config/route.php';
            self::$config = $conf['default'];
            $url = $request->server['request_uri'];
            if($url == '/favicon.ico' || empty($url)){
                return false;
            }
            if($url != '/'){
                $url = trim($url,'/');
            }else{
                $url = $url;
            }
            if(!empty(self::$config['suffix'])){
                foreach(self::$config['suffix'] as $key => $value){
                    if(substr($url,-strlen($value)) == $value){
                        $url = substr($url,0,strlen($url)-strlen($value));
                        break;
                    }
                }
            }
            //handle the custom routing in files
            if(!empty($route)){
                foreach($route as $route_key =>$route_value){
                    if(strtolower(substr($url,0,strlen($route_key))) == strtolower($route_key)){
                        $define_route = $route_value;
                        break;
                    }else{
                        $define_route = '';
                    }
                }
            }else{
                return false;
            }
            if(!$define_route){
                return false;
            }
            $url = array_values(array_filter(explode('/',$url)));
            if(count($url)>0){
                if(strpos($url[0],'.php')){
                    array_shift($url);
                }
            }
            self::$controller =ucfirst(array_shift($url));
            self::$module     = self::$config['module'];
            $count = count($url);
            $pattern = '/^(0|[1-9]\d*)/';
            $request_method = strtolower($request->server['request_method']);
            foreach($define_route as $val){
                if($request_method == strtolower($val['0']) && $request_method == 'get' && $count === 0 && count(explode('/',$val[1]))===1) {
                    self::$action=null;self::$args=null;
                    self::$action = strtolower(explode('/',$val[2])[1]);
                }elseif($request_method == strtolower($val['0']) && $request_method == 'get' && $count === 1 && count(explode('/',$val[1]))===2 && count(explode('/',$val[3]))===1){
                    if(preg_match_all($pattern,$url[0])){
                        self::$action=null;self::$args=null;
                        self::$action = strtolower(explode('/',$val[2])[1]);
                        self::$args = array_slice($url,0);
                    }
                }elseif($request_method == strtolower($val['0']) && $request_method == 'get' && count(explode('/',$val[1]))===1){
                    if(!preg_match_all($pattern,$url[0])){
                        self::$action=null;self::$args=null;
                        self::$action = strtolower(explode('/',$val[2])[1]);
                        self::$args = array_slice($url,0);
                    }
                }elseif($request_method == strtolower($val['0']) && $request_method == 'post' && $count === 0 && count(explode('/',$val[1]))===1){
                    self::$action=null;self::$args=null;
                    self::$action = strtolower(explode('/',$val[2])[1]);
                }elseif($request_method == strtolower($val['0']) && $request_method == 'put' && $count === 1 && count(explode('/',$val[1]))===2 && count(explode('/',$val[3]))===1){
                    if(preg_match_all($pattern,$url[0])){
                        self::$action=null;self::$args=null;
                        self::$action = strtolower(explode('/',$val[2])[1]);
                        self::$args = array_slice($url,0);
                    }
                }elseif($request_method == strtolower($val['0']) && $request_method == 'delete' && $count === 1 && count(explode('/',$val[1]))===2 && count(explode('/',$val[3]))===1){
                    if(preg_match_all($pattern,$url[0])){
                        self::$action=null;self::$args=null;
                        self::$action = strtolower(explode('/',$val[2])[1]);
                        self::$args = array_slice($url,0);
                    }
                }
            }
            $class_name = "\\".self::$module."\\controller\\".self::$controller;
            $app_path = $conf['default']['app_path'];
            $class_file = $app_path.str_replace('\\',DIRECTORY_SEPARATOR,$class_name).'.php';
            if(is_file($class_file)){
                return ['classname'=>$class_name,'action'=>self::$action,'param'=>self::$args];
            }else{
                return false;
            }
        }else{
            self::$config = $conf['api']['router'];
            $url = $request->server['request_uri'];
            //Determine uri path
            if($url == '/favicon.ico' || empty($url)){
                return false;
            }
            if($url != '/'){
                $url = trim($url,'/');
            }else{
                $url = $url;
            }
            //handle the suffix of the request URI
            if(!empty(self::$config['suffix'])){
                foreach(self::$config['suffix'] as $key => $value){
                    if(substr($url,-strlen($value)) == $value){
                        $url = substr($url,0,strlen($url)-strlen($value));
                            break;
                    }
                }
            }
            //handle the custom routing in files
            if(!empty(self::$config['custom_route'])){
                foreach(self::$config['custom_route'] as $route_key =>$route_value){
                    if(substr($url,0,strlen($route_key)) == $route_key){
                        $url = str_replace($route_key,$route_value,$url);
                            break;
                    }
                }
            }
                //handle the first param of the request URI 
            $url = array_values(array_filter(explode('/',$url)));
            if(count($url)>0){
                if(strpos($url[0],'.php')){
                    array_shift($url);
                }
            }
            $count = count($url);
            if($count >= 3){
                self::$module     = $url[0];
                self::$controller = ucfirst($url[1]);
                self::$action     = $url[2];
                self::$args       = array_slice($url,3);
            }elseif($count == 2){
                self::$module     = self::$config['module'];
                self::$controller = ucfirst($url[0]);
                self::$action     = $url[1];
            }elseif($count==1 && $url != ''){
                self::$module     = self::$config['module'];
                self::$controller = ucfirst($url[0]);
                self::$action     = self::$config['action'];
            }else{
                self::$module     = self::$config['module'];
                self::$controller = ucfirst(self::$config['controller']);
                self::$action     = self::$config['action'];
            }
            $class_name = "\\".self::$module."\\controller\\".self::$controller;
            $app_path = $conf['default']['app_path'];
            $class_file = $app_path.str_replace('\\',DIRECTORY_SEPARATOR,$class_name).'.php';
            if(is_file($class_file)){
                return ['classname'=>$class_name,'action'=>self::$action,'param'=>self::$args];
            }else{
                return false;
            }
        }
    }
}
