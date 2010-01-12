<?php

// お気に入り作成時にはてなブックマークにもマルチポストするプラグイン

class HatenaBookmarkPlugin extends TwitterProxyPlugin {
	// @param $request HTTP_Request オブジェクト
	function willRequest($request) {
		// お気に入り作成をフックする
		if(preg_match("|^/favorites/create/(\d+)|",$this->server->request['path'],$match)) {

			$id = $match[1];
			
			$url = $this->server->config['Twitter']['api'];
			$url .= '/status/show/'.$id.'.json';
			$req = new HTTP_Request($url);
			if(isset($_SERVER["PHP_AUTH_USER"])) {
				$req->setBasicAuth($_SERVER["PHP_AUTH_USER"],@$_SERVER["PHP_AUTH_PW"]);
			}
			$result = $req->sendRequest();
			if(PEAR::isError($result)) {
				return;
			}
			if($req->getResponseCode() != 200) {
				return;
			}
			
			$json = json_decode($req->getResponseBody());
			
			$title = $json->text;
			$href = 'http://twitter.com/'.$json->user->screen_name.'/status/'.$id;
		
			$created = date('Y-m-d\TH:i:s\Z');
			$nonce = pack('H*', sha1(md5(time())));
			$pass_digest = base64_encode(pack('H*', sha1($nonce.$created.$this->server->config['Plugin']['HatenaBookmark']['password'])));
			$wsse = 'UsernameToken Username="'.$this->server->config['Plugin']['HatenaBookmark']['id'] . '", ';
			$wsse .= 'PasswordDigest="'.$pass_digest.'", ';
			$wsse .= 'Nonce="'.base64_encode($nonce).'",';
			$wsse .= 'Created="'.$created.'"';
			

			$req = new HTTP_Request('http://b.hatena.ne.jp/atom/post');
			$req->setMethod(HTTP_REQUEST_METHOD_POST);
			$req->addHeader('WWW-Authenticate','WSSE profile="UsernameToken"');
			$req->addHeader('X-WSSE',$wsse);
			$req->addHeader('Content-Type', 'application/x.atom+xml');

			$xml = '<?xml version="1.0" encoding="utf-8"?>' .
			'<entry xmlns="http://purl.org/atom/ns#">' .
			'<title>' . $title . '</title>' .
			'<link rel="related" type="text/html" href="' . $href . '" />' .
			'<summary type="text/plain"></summary>' .
			'</entry>';
			$req->addRawPostData($xml);
			
			$req->sendRequest();
		}
		
		return $request;
	}

}





?>