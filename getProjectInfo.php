<?php

	require_once 'DSN.php';
	require_once 'project.php';

	$pdo = db_connect();

	$pNo = $_POST['pNo'];
	$info = getProjectInfo($pdo, $pNo);

	header("Content-Type: application/json; charset=UTF-8");
	$jsonStr = json_encode($info);
	echo $jsonStr;

	exit;

?>
