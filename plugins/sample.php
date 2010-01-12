<?php

class SamplePlugin {
	function __construct($server) {
	}
	
	function willRequest($request) {
		require_once 'Net/URL.php';
		$url = new Net_URL($request->getURL());
		if(preg_match("/\/statuses\//",$url->path)) {
			// status 取得時は強制200件取得にする
			$request->addQueryString('count','200');
		}
		
		return $request;
	}
}



?>