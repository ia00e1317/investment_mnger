<?php

// DB接続情報
define('user', '●●●');
define('pass', '●●●●●');
define('dsn', 'mysql:host=mysql643.db.sakura.ne.jp;dbname=●●●●●;charset=utf8');

	define('encryptKey', 'tress');
	define('log_path', '/home/●●●/www/investmng/log/debug.log');

function db_connect() {
	$pdo = new PDO(dsn, user, pass, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
	return $pdo;
}

?>
