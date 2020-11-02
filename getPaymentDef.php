<?php

	require_once 'DSN.php';
	require_once 'payment_def.php';

	$pdo = db_connect();

	$pNo = $_POST['pNo'];
	$type = $_POST['type'];
	$info = getPaymentDefList($pdo, $pNo, $type);

	header("Content-Type: application/json; charset=UTF-8");
	$jsonStr = json_encode($info);
	echo $jsonStr;

	exit;

?>
