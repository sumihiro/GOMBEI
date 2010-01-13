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
		
		$req = $this->createTwitterRequest();
		
		$req = $this->hook('willRequest',$req);
		
		
		$result = $req->sendRequest();
		if(PEAR::isError($result)) {
			$this->error($result);
			return false;
		}
		
		$reason = method_exists($req,'getResponseReason') ? $req->getResponseReason() : '';
		
		$response = new TwitterProxyServerResponse($req->getResponseCode(),$reason,$req->getResponseHeader(),$req->getResponseBody());
		
		$response = $this->hook('willResponse',$response);
		
		// レスポンス表示
		header('HTTP/1.0 '.$response->getResponseCode().' '.$response->getResponseReason());
		foreach($response->getResponseHeader() as $k => $v) {
			switch($k) {
			case 'content-type':
				$this->header('Content-Type',$v);
				break;
			case 'www-authenticate':
				$this->header('WWW-Authenticate',$v);
				break;
			}
		}
		
		echo $response->getResponseBody();
		
		
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
	
	function createTwitterRequest() {
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
		
		return $req;
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

class TwitterProxyServerResponse {
	var $_code;
	var $_reason;
	var $_header;
	var $_body;
	function __construct($code,$reason,$header,$body) {
		$this->_code = $code;
		$this->_reason = $reason;
		$this->_header = $header;
		$this->_body = $body;
	}
	
	function getResponseCode() {
		return $this->_code;
	}
	function setResponseCode($code) {
		$this->_code = $code;
	}
	
	function getResponseReason() {
		return $this->_reason;
	}
	function setResponseReason($reason) {
		$this->_reason = $reason;
	}
	
	function getResponseHeader() {
		return $this->_header;
	}
	function setResponseHeader($header) {
		$this->_header = $header;
	}
	
	function getResponseBody() {
		return $this->_body;
	}
	function setResponseBody($body) {
		$this->_body = $body;
	}
}

class TwitterProxyPlugin {
	var $server;
	
	// @param $server TwitterProxyServer オブジェクト
	function __construct($server) {
		$this->server = $server;
	}
	
	/*
	// @param $request HTTP_Request オブジェクト
	function willRequest($request) {
		
		return $request;
	}
	*/
	
	/*
	// @param $response TwitterProxyServerResponse オブジェクト
	function willResponse($response) {
		
		return $response;
	}
	*/
	
}

?>