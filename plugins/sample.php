<?php

class SamplePlugin {
	var $server;
	function __construct($server) {
		$this->server = $server;
	}
	
	function willRequest($request) {
		if(strpos($this->server->request['path'],'/statuses/') === 0) {
			// status 取得時は強制200件取得にする
			$request->addQueryString('count','200');
		}
		
		return $request;
	}
}



?>