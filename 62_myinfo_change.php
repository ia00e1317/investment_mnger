<?php

session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

require_once 'DSN.php';
require_once 'security.php';
require_once 'operator.php';
require_once 'security.php';

ob_start();

try {

	// DB接続
	$pdo = db_connect();

	if (isset($_POST['form_submit_button'])) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// 入力値取得
		$info = new operator();
		$info->setId($_SESSION['loginUserID']);
		$info->setLastName($_POST['operator_name_1']);
		$info->setFirstName($_POST['operator_name_2']);
		$info->setPassword($_POST['operator_pass']);
		$info->setPassConfirm($_POST['operator_pass_confirm']);
		$info->setShortName($_POST['operator_code']);
		$info->setMailAddress($_POST['mail_address']);
		$info->setTelHome($_POST['phone']);
		$info->setTelMobile($_POST['mobilephone']);

		// パスワード不備
		if (!empty($info->getPassword())
				&& strcmp($info->getPassword(), $info->getPassConfirm()) != 0) {

			// エラーメッセージ設定
			$errorMessage = 'パスワードとパスワード(確認)が異なります。';
			$errMsgOutput = '<p style="color:red; margin-top: 0;">' . htmlspecialchars($errorMessage, ENT_QUOTES) . '</p>';

		// 正常
		} else {

			// 更新実行
			updateOperator($pdo, $info);

			// 「投資案件リスト」画面へ移動
			header ( 'Location: 61_operator_menu.php' );
			exit ();
		}

	// 初期表示
	} else {

		// マイ情報取得
		$info = getOperatorInfo($pdo, $_SESSION ['loginUserID']);
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
<title>オペレータ情報修正（マイ情報修正）</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" href="css/mailform.css" />
<link rel="stylesheet" href="css/thanks.css" />

</head>
<body>
<div id="main">
<form action="" method="post" id="mail_form">
	<h1>オペレータ情報修正（マイ情報修正）</h1>
	<dl>
		<dt>名前<span>Operator Name</span></dt>
		<dd class="required">
			<input type="text" id="name_1" name="operator_name_1" value="<?= h($info->getLastName()) ?>"
				maxlength="20" required="required"/>
			<input type="text" id="name_2" name="operator_name_2" value="<?= h($info->getFirstName()) ?>"
				maxlength="20" required="required"/>
		</dd>

		<dt>ID<span>ID</span></dt>
		<dd>
			<?= h($info->getId()) ?>
		</dd>
		<dt>パスワード<span>Password</span></dt>
		<dd>
			<input type="password" id="password" name="operator_pass" value="<?= h($info->getPassword()) ?>"
				maxlength="20" />
		</dd>

		<dt>
			パスワード<br />(確認用)<span>Password Confirm</span></dt>
		<dd>
			<?php
				// エラーが有った場合のメッセージ出力場所
				if (isset($errMsgOutput)) { echo $errMsgOutput; }
			?>
			<input type="password" id="password" name="operator_pass_confirm" value="" />
		</dd>

		<dt>
			コード<span>Code</span>
		</dt>
		<dd>
			<?= h($info->getShortName()) ?>　　※覚書コードに付与されるオペレーターコード
			<input type="hidden" id="text" name="operator_code" value="<?php echo $info->getShortName(); ?>"
				maxlength="6" size="3" style="background-color:#e9e9e9;" readonly/><!--required="required" -->
		</dd>

		<dt>メールアドレス<span>Mail Address</span></dt>
		<dd class="required">
			<input type="email" id="mail_address" name="mail_address" value="<?= h($info->getMailAddress()) ?>"
				maxlength="50" required="required"/>
		</dd>

		<dt>電話番号<span>Phone Number</span></dt>
		<dd>
			<input type="tel" id="phone" name="phone" value="<?= h($info->getTelHome()) ?>"
				maxlength="20" required="required"/>
		</dd>

		<dt>携帯電話番号<span>Mobile Phone Number</span></dt>
		<dd>
			<input type="tel" id="phone" name="mobilephone" value="<?= h($info->getTelMobile()) ?>"
				maxlength="20" />
		</dd>
	</dl>

	<p id="form_submit" class="center">
		<input type="submit" name="form_submit_button" value="修正する" />
	</p>
	<p id="form_submit" class="right">
		<input type="button" id="form_cancel_button" value="オペレータ管理メニューへ戻る" onClick="location.href='61_operator_menu.php'">
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
