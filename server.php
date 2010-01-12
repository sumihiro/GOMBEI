<?php
require_once 'HTTP/Request.php';

class TwitterProxyServer {
	var $config;
	var $plugins;
	
	var $request;
	
	private $pluginPostfix = 'Plugin';
	
	function __construct($config) {
		$this->config = $config;
	}
	
	function dispatch() {
		//var_dump($_SERVER);
		
		$this->loadRequest();
		
		$this->loadPlugins();
		
		$url = $this->config['Twitter']['api'];
		if(isset($_SERVER['PATH_INFO'])) {
			$url .= $_SERVER['PATH_INFO'];
		}
		if(isset($_SERVER['QUERY_STRING'])) {
			$url .= '?'.$_SERVER['QUERY_STRING'];
		}
		
		$option = array(
			'allow_redirect' => false
		);
		
		$req = new HTTP_Request($url,array_merge($this->config['HTTP_Request'],$option));
		$req->setMethod($_SERVER['REQUEST_METHOD']);
		if(isset($_SERVER["PHP_AUTH_USER"])) {
			$req->setBasicAuth($_SERVER["PHP_AUTH_USER"],@$_SERVER["PHP_AUTH_PW"]);
		}
		foreach($_POST as $k => $v) {
			$req->setPostData($k,$v);
		}
		
		$req = $this->hook('willRequest',$req);
		
		
		$result = $req->sendRequest();
		if(PEAR::isError($result)) {
			$this->error($result);
			return false;
		}
		
		// レスポンス表示
		header('HTTP/1.0 '.$req->getResponseCode().' '.$req->getResponseReason());
		foreach($req->getResponseHeader() as $k => $v) {
			switch($k) {
			case 'content-type':
				$this->header('Content-Type',$v);
				break;
			case 'www-authenticate':
				$this->header('WWW-Authenticate',$v);
				break;
			}
		}
		
		echo $req->getResponseBody();
		
		
		return true;
	}
	
	function loadRequest() {
		$this->request = array(
			'method' => $_SERVER['REQUEST_METHOD'],
			'path' => @$_SERVER['PATH_INFO'],
			'query' => $_SERVER['QUERY_STRING'],
			'get' => $_GET,
			'post' => $_POST,
		);
	}
	
	function loadPlugins() {
		$dirname = './plugins';
		$dir = opendir($dirname);
		while(($file = readdir($dir)) !== false) {
			if($file == '.' || $file == '..') {
				continue;
			}
			if(preg_match("/(.*)\.php/",$file,$match)) {
				$class = $match[1] . $this->pluginPostfix;
				include($dirname . '/' .$file);
				if(class_exists($class)) {
					$this->plugins[] = new $class($this);
				}
			}
		}
		closedir($dir);
	}
	
	function hook($action,$param = null) {
		foreach($this->plugins as $p) {
			if(method_exists($p,$action)) {
				$param = call_user_func(array($p,$action),$param);
			}
		}
		return $param;
	}
	
	function header($k,$v) {
		header($k.': '.$v);
	}
	
	function error($error) {
		header('HTTP/1.0 500 Internal Server Error');
		echo $error->getMessage();
		echo $error->getDebugInfo();
	}
}



?>