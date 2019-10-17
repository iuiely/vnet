<?php
namespace Vnet\web;
use Vnet\Singleton;
use Vnet\web\HttpRequest;

class HttpApplication{
    Use Singleton;    
    private static $classMap =[];
    public static $debug = true;
    private static $config;
    public $server = null;
    public $request = null;
    public $response = null;
    public function http_run($server,$request,$response){
        $this->server = $server;
        $req = HttpRequest::getInstance();
        $req->set($request);
        $this->request = $req;
        $this->response = $response;
        $this->response->header('Content-type', 'text/html;charset=utf-8');
        if($this->request->server['request_uri'] =='/favicon.ico') {
            $response->status(404);
            $response->header('Content-Type','application/json; charset=UTF-8');
            return $response->end(json_encode([
                'status'=>'404','error' => 'Not Found',
            ]));
        }
        $route = HttpRoute::getInstance()->http_route($this->request);
        if(!$route){
            $response->status(400);
            $response->header('Content-Type','application/json; charset=UTF-8');
            return $response->end(json_encode([
                'status'=>'400','error' => 'Bad Request',
            ]));
        }
        $classname = $route['classname'];
        $action = $route['action'];
        $args = $route['param'];
        if (!class_exists($classname)) {
            $response->status(500);
            $response->header('Content-Type','application/json; charset=UTF-8');
            return $response->end(json_encode([
                'status'=>'500','error' => 'Internal Server Error',
            ]));
        }
        if(!isset(self::$classMap[$classname])){
            //\ReflectionClass::export($classname);
            $class = new $classname;
            self::$classMap[$classname] = $class;
        }
        if(!method_exists(self::$classMap[$classname],$action)){
            $response->status(405);
            $response->header('Content-Type','application/json; charset=UTF-8');
            return $response->end(json_encode([
                'status' => 405,'error' => 'Method Not Allowed',
            ]));
        }
        try{
            if(!$args){
                if(!empty(ob_get_contents ())) ob_end_clean ();
                ob_start();
                self::$classMap[$classname]->response = $this->response;
                self::$classMap[$classname]->request = $this->request;
                self::$classMap[$classname]->server = $this->server;
                $result = self::$classMap[$classname]->$action();
                $content = ob_get_contents();
                ob_end_clean();
                if($result){
                    $response->end($result);
                }else{
                    $response->end($content);
                }
            }else{
                if(!empty(ob_get_contents ())) ob_end_clean ();
                ob_start();
                self::$classMap[$classname]->response = $this->response;
                self::$classMap[$classname]->request = $this->request;
                self::$classMap[$classname]->server = $this->server;
                $result = self::$classMap[$classname]->$action($args);
                $content = ob_get_contents();
                ob_end_clean();
                if($result){
                    $response->end($result);
                }else{
                    $response->end($content);
                }
            }
        }catch(\Exception $e){
                $response->header('Content-type',"text/html;charset=utf-8;");
                $response->status(404);
                $response->end('PAGE NOT FOUND');
                return ;
        }
    }
}
