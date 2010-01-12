<?php
require_once 'HTTP/Request.php';

class TwitterProxyServer {
	var $config;
	
	function __construct($config) {
		$this->config = $config;
	}
	
	function dispatch() {
		//var_dump($_SERVER);
		
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