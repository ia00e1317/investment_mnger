<?php

	require_once 'DSN.php';
	require_once 'investor.php';

	$pdo = db_connect();

	$type = $_POST['sType'];		//ポストで受け取れる
	$val = $_POST['sValue'];		//ポストで受け取れる

	if ($type == '1') {
		// 個人番号で検索
		$investorList = selectInvestorListByNo($pdo, $val);
	} else {
		// 投資家名で検索
		$investorList = selectInvestorListByName($pdo, $val);
	}

	header("Content-Type: application/json; charset=UTF-8");
	$jsonStr = json_encode($investorList);
	echo $jsonStr;

	exit;

?>
