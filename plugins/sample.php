<?php

class SamplePlugin extends TwitterProxyPlugin {
	// @param $request HTTP_Request オブジェクト
	function willRequest($request) {
		if(strpos($this->server->request['path'],'/statuses/') === 0) {
			// status 取得時は強制200件取得にする
			$request->addQueryString('count','200');
		}
		
		return $request;
	}
}



?>