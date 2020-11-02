<?php

session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

require_once 'DSN.php';
require_once 'investor.php';
require_once 'security.php';

ob_start();

try {

	// DB接続
	$pdo = db_connect();

	// 削除ボタン「form_submit_button」が押された際の処理
	if (isset ( $_POST ['form_submit_button'] )) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// 削除実行
		deleteInvestor($pdo, $_SESSION ['investor_no']);

		// セッションクリア
		unset($_SESSION['investor_no']);

		// 投資家情報一覧(削除)画面へ遷移
		header ( 'Location: 15_investor_list_delete.php' );
		exit ();

	// 初期表示
	} else {

		// 投資家情報取得
		$info = getInvestorInfo($pdo, $_POST ['investor_no']);

		// セッション登録
		$_SESSION['investor_no'] = $_POST['investor_no'];
	}

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
	<title>投資家情報削除</title>
	<meta name="robots" content="noindex,nofollow" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0" />
	<link rel="stylesheet" href="css/reset.css" />
	<link rel="stylesheet" href="css/style.css" />
	<link rel="stylesheet" href="css/mailform.css" />
	<link rel="stylesheet" href="css/thanks.css" />
	<style type="text/css">
		input[name="distribution"] {
			width: 10%;
		}
	</style>

<!-- ご希望の日時選択ライブラリここから -->
<link rel="stylesheet" href="css/jquery.datetimepicker.css" />
<!-- ご希望の日時選択ライブラリここまで -->
</head>
<body>
<div id="main">
<form action="" method="post" id="mail_form">
	<h1>投資家情報削除</h1>
	<dl>
		<dt>種別<span>type</span></dt>
		<dd>
			<ul>
				<li>
					<label>
						<input type="radio" class="type" name="type" value="一般"
							checked="<?php if (strcmp($info->getType(), '01') == 0) {echo 'checked';} ?>" disabled="disabled"/>一般
					</label>
				</li>
				<li>
					<label>
						<input type="radio" class="type" name="type" value="特別"
							checked="<?php if (strcmp($info->getType(), '02') == 0) {echo 'checked';} ?>" disabled="disabled"/>特別
					</label>
				</li>
			</ul>
		</dd>

		<dt>投資家名<span>Investor Name</span></dt>
		<dd class="required"><?= h($info->getLastName().' '.$info->getFirstName()) ?></dd>

		<dt>ふりがな<span>Name Reading</span></dt>
		<dd><?= h($info->getLastNameKana().' '.$info->getFirstNameKana()) ?></dd>

		<dt>個人ナンバー<span>personal number</span></dt>
		<dd><?= h($info->getNo()) ?></dd>

		<dt>メールアドレス<span>Mail Address</span></dt>
		<dd class="required"><?= h($info->getMailAddress()) ?></dd>

		<dt>郵便番号<span>Postal</span></dt>
		<dd class="required"><?= h($info->getPostCode()) ?> </dd>

		<dt>住所<span>Address</span></dt>
		<dd><?= h($info->getAddress()) ?></dd>

		<dt>電話番号<span>Phone Number</span></dt>
		<dd><?= h($info->getTel()) ?></dd>

		<dt>紹介投資家<span>Introducing investor</span></dt>
		<dd><?= h($info->getIntroducedInvestorName()) ?></dd>

		<dt>銀行名<span>Bank name</span></dt>
		<dd><?= h($info->getBankName()) ?></dd>

		<dt>支店番号<span>branch number</span></dt>
		<dd><?= h($info->getBranchNo()) ?></dd>

		<dt>口座種別<span>type of account </span></dt>
		<dd>
			<ul>
				<li>
					<label>
						<input name="gender" type="radio" class="gender" value="普通"
							checked="<?php if (strcmp($info->getAccountType(), '普通') == 0) {echo 'checked';} ?>" disabled="disabled"/>普通
					</label>
				</li>
				<li>
					<label>
						<input type="radio" class="gender" name="gender" value="当座"
							checked="<?php if (strcmp($info->getAccountType(), '当座') == 0) {echo 'checked';} ?>" disabled="disabled"/>当座
					</label>
				</li>
			</ul>
		</dd>

		<dt>口座番号<span>account number</span></dt>
		<dd><?= h($info->getAccountNo()) ?></dd>

		<dt>口座名義<span>account holder</span></dt>
		<dd><?= h($info->getAccountName()) ?></dd>

		<dt>登録費用<span>registration fee</span></dt>
		<dd><?= h($info->getRegistrationFee()) ?>円</dd>

		<dt>紹介者配分割合<span>distribution</span></dt>
		<dd class="required"><?= h($info->getIntroducerDistribution()) ?> ％
			<span id="distributionOutput"><?= floor(((int)$info->getRegistrationFee())*((float)$info->getIntroducerDistribution())/100) ?></span> 円 </dd>
	</dl>

	<p id="form_submit" class="center">
		<input type="submit" name="form_submit_button" value="削除する" />
	</p>
	<p id="form_submit" class="right">
		<input id="form_cancel_button" type="button" value="戻る" onClick="location.href='15_investor_list_delete.php'">
	</p>
	<p id="form_submit" class="right" >
		<input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>

	<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
</form>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>

</body>
</html>
