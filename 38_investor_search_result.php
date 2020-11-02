<?php

session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

require_once 'DSN.php';
require_once 'investor.php';
require_once 'payment.php';
require_once 'security.php';

try {

	// DB接続
	$pdo = db_connect();

	// 「支払い」「取消」が押された場合
	if (isset($_POST['funcId'])) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// 更新
		if (strcmp($_POST['funcId'], '1') == 0) {
			updateActualPayment($pdo, $_POST['tranId'], $_POST['payment_date'], $_POST['payment_amount']);

		// 取消
		} else if (strcmp($_POST['funcId'], '2') == 0) {
			updateActualPayment($pdo, $_POST['tranId'], null, null);
		}

	// 初期表示時
	} else {

		// セッション登録
		$_SESSION['investor_no'] = $_POST['investor_no'];
		$_SESSION['investor_date'] = $_POST['investor_date'];
	}

	// 検索条件
	$investor = getInvestorInfo($pdo, $_SESSION['investor_no']);

	// 検索結果
	$paymentList = getPaymentListByInvestor($pdo, $_SESSION['investor_no'], $_SESSION['investor_date']);

	//配当種類表示
	$typeLabel = array( "01" => '配当', "02" => 'オプション', "03" => '元本償還' );

	// CSRF対策用トークン取得
	$csrf_token = setCSRFtoken();

} catch (PDOException $e) {
	echo '<script type="text/javascript">alert("データベース接続・操作処理エラー")</script>';
} finally {
	$pdo = null;
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<title>投資家検索結果</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" href="css/mailform.css" />
<link rel="stylesheet" href="css/maildiv.css" />
<link rel="stylesheet" href="css/thanks.css" />
</head>
<body>
<div id="main">
<div id="mail_form">

	<h1>投資家検索結果</h1>
	<dl>
		<dt>投資家名：<?= h($investor->getLastName().$investor->getFirstName()) ?></dt>
		<dd>
			対象年月：<?= date('Y年n月', strtotime($_SESSION['investor_date'])) ?>
		</dd>

		<?php foreach ($paymentList as $value) { ?>
		<dt>案件名：<?= h($value['projectName']) ?><br><?= h($typeLabel[$value['paymentType']]) ?></dt>
		<dd>
			<form action="" method="post">
				<ul id="thanks2">
					<li> 支払予定日：</li>
					<li><?= date('Y年n月j日', strtotime($value['plannedDate'])) ?></li>
				</ul>
				<ul id="thanks2">
					<li> 支払実績日：</li>
					<li>
						<?php if (is_null($value['actualDate'])): ?>
							<input class="form_submit_button" type="date" name="payment_date" value="" required="required"/>
						<?php else: ?>
							<?= date('Y年n月j日', strtotime($value['actualDate'])) ?>
						<?php endif; ?>
					</li>
				</ul>
				<ul id="thanks2">
					<li> 支払予定額：</li>
					<li><?= number_format($value['plannedAmount']) ?> 円</li>
				</ul>
				<ul id="thanks2">
					<li> 支払実績額：</li>
					<li>
						<?php if (is_null($value['actualAmount'])): ?>
							<input class="form_submit_button" type="number" name="payment_amount"
								min="1" step="1" value="" required="required"/>円
						<?php else: ?>
							<?= number_format($value['actualAmount']); ?> 円
						<?php endif; ?>
					</li>
					<li>
						<?php if (is_null($value['actualAmount'])): ?>
							<input type="hidden" name="funcId" value="1">
							<input type="submit" id="form_submit_button" value="支払い" />
						<?php else: ?>
							<input type="hidden" name="funcId" value="2">
							<input type="submit" id="form_submit_button" value="取消" />
						<?php endif; ?>
					</li>
				</ul>

				<input type="hidden" name="tranId" value="<?= $value['transNo'] ?>">
				<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
			</form>
		</dd>
		<?php } ?>
	</dl>

	<p id="form_submit" class="right">
		<input type="button" id="form_cancel_button" value="支払状況確認へ戻る" onClick="location.href='37_paymentstatus_search.php'">
	</p>
	<p id="form_submit" class="right" >
		<input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>

</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>

</body>
</html>
