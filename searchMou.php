<?php

	require_once 'DSN.php';
	require_once 'payment.php';

	$pdo = db_connect();

	$pNo = $_POST['pNo'];
	$iNo = $_POST['iNo'];
	$hDate = $_POST['hDate'];

	$mouList = getPaymentListForUptdate($pdo, $pNo, $iNo, $hDate);

	header("Content-Type: application/json; charset=UTF-8");

	$jsonStr = json_encode($mouList);
	echo $jsonStr;

	exit;

?>
