<?php
// Twitterの俺俺プロキシを作るプロジェクト。
// by Sumihiro Ueda http://twitter.com/sumihiro

require_once 'settings.php';
require_once 'server.php';

$server = new TwitterProxyServer($config);
$server->dispatch();


?>