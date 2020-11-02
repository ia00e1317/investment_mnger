<?php

	require_once 'DSN.php';
	require_once 'payment.php';
	require_once 'project_attr.php';

	$pdo = db_connect();

	$tranArray = $_POST['tranArray'];
	$haito = $_POST['haito'];

	$result = FALSE;
	try {

		foreach ($tranArray as $tranNo) {

			$paymentInfo = getPayment($pdo, $tranNo);
			$projectInfo = getProjectAttr($pdo, $paymentInfo['attrNo']);

			$info = new payment();
			$info->setTransNo($tranNo);
			$info->setCommission($haito);
			$info->setPlannedAmount(intval($projectInfo->getInvestmentAmount()) * $haito / 100);
			updateHaito($pdo, $info);
		}

		$pdo->commit();

		$result = true;

	} catch (Exception $e) {
		// エラー
	} finally {
		$pdo = null;
	}

	header("Content-Type: application/json; charset=UTF-8");

	$jsonStr = json_encode($result);
	echo $jsonStr;

	exit;

?>
