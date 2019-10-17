<?php
namespace Vnet\web;
use Vnet\Singleton;

class HttpRequest {
    Use Singleton;
    private $server;
    private $header;
//    private $request;
    private $post;
    private $get;
    private $cookie;
    private $files;
    private $tmpfiles;
    private $rawContent;
    private $getData;

    public function set($request){
        $this->server       = $request->server;
        $this->header       = $request->header;
        $this->tmpfiles     = $request->tmpfiles;
//        $this->request      = $request->request;
        $this->cookie       = $request->cookie ;
        $this->get          = $request->get ;
        $this->files        = $request->files ;
        $this->post         = $request->post ;
        $this->rawContent   = $request->rawContent();
        $this->getData      = $request->getData();
    }

    public function __get($name){
        return $this->$name;
    }

    public function get($name = null){
        if (!$this->get){
            return null;
        }
        if (is_array($name) || $name == null) {
            $data = [];
            foreach ($this->get as $k=>$v){
                if ($name != null) {
                    if (is_array($name) && in_array($k, $name)) {
                        $data[$k] = $v;
                    }else{
                        return null;
                    }
                }else{
                    $data[$k] = $v;
                }
            }
            return $data;
        }
        if (is_string($name)){
            return $this->get[$name] ?? null;
        }
    }

    public function post($name = null){
        if (!$this->post){
            return null;
        }
        if (is_array($name) || $name == null) {
            $data = [];
            foreach ($this->post as $k=>$v){
                if ($name != null) {
                    if (is_array($name) && in_array($k, $name)) {
                        $data[$k] = $v;
                    }else{
                        return null;
                    }
                }else{
                    $data[$k] = $v;
                }
            }
            return $data;
        }
        if (is_string($name)){
            return $this->post[$name] ?? null;
        }
    }

    public function get_files(){
        return $this->files;
    }

    public function get_file($key){
        return $this->files[$key];
    }
    public function get_rawbody(){
        return $this->rawContent;
    }
    public function get_server(){
        return $this->server;
    }

    public function get_header(){
        return $this->header;
    }
    public function get_method(){
        if(!$this->server){
            return null;
        }
        return $this->server['request_method'];
    }
}
