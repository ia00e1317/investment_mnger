<?php
session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (! isset ( $_SESSION ['loginUserID'] )) {
	header ( 'Location: 02_logout.php' );
	exit ();
}

require_once 'DSN.php';
require_once 'operator.php';
require_once 'security.php';

try {

	// DB接続
	$pdo = db_connect();

	// 「登録する」ボタンが押された際の処理
	if (isset ( $_POST ['form_submit_button'] )) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// 入力値取得
		$info = new operator();
		$info->setId($_POST['operator_id']);
		$info->setLastName($_POST['last_name']);
		$info->setFirstName($_POST['first_name']);
		$info->setPassword($_POST['operator_pass']);
		$info->setPassConfirm($_POST['operator_pass_confirm']);
		$info->setShortName($_POST['operator_code']);
		$info->setMailAddress($_POST['operator_mail_address']);
		$info->setTelHome($_POST['operator_phone']);
		$info->setTelMobile($_POST['operator_mobilephone']);

		// エラーメッセージ用の変数を初期化
		$errorMessage = '';

		// パスワード不備の場合
		if (strcmp($info->getPassword(), $info->getPassConfirm()) != 0) {

			// エラーメッセージ設定
			$errorMessage = 'パスワードとパスワード(確認)が異なります。';
			$errMsgOutput = '<p style="color:red; margin-top: 0;">' . htmlspecialchars($errorMessage, ENT_QUOTES) . '</p>';

			// パスワード確認欄クリア
			$info->setPassConfirm('');

		} else {

			// IDの重複チェック
			if (!isValidID($pdo, $info->getId())) {

				// エラーメッセージ設定
				$errorMessage2 = '登録済みIDです。';
				$errMsgOutput2 = '<p style="color:red; margin-top: 0;">' . htmlspecialchars($errorMessage2, ENT_QUOTES) . '</p>';

			} else {

				// 新規登録実行
				insertOperator($pdo, $info);

				// 「オペレータ管理メニュー」画面へ移動
				header('Location: 61_operator_menu.php');
				exit();
			}
		}

	// 初期表示
	} else {

		// 画面表示情報取得
		$info = new operator();
	}

	//利用中オペレーターコード取得
	$operatorCodeList = getOperatorCodeList($pdo);

	// CSRF対策用トークン取得
	$csrf_token = setCSRFtoken();

} catch (PDOException $e) {
	echo "<script type=\"text/javascript\">alert(\'データベース接続・操作処理エラー\');</script>";
} finally {
	$pdo = null;
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<title>オペレータ新規登録</title>
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
	<h1>オペレータ新規登録</h1>
	<dl>

		<dt>
			名前<span>Operator Name</span>
		</dt>
		<dd class="required">
			<input type="text" id="name_1" name="last_name" value="<?php echo $info->getLastName(); ?>"
				maxlength="20" required="required"/>
			<input type="text" id="name_2" name="first_name" value="<?php echo $info->getFirstName(); ?>"
				maxlength="20" required="required"/>
		</dd>

		<dt>
			ID<span>ID</span>
		</dt>
		<dd>
			<?php
				// エラーが有った場合のメッセージ出力場所
				if (isset($errMsgOutput2)) {echo $errMsgOutput2;}
			?>
			<input type="text" id="text" name="operator_id" value="<?php echo $info->getId(); ?>"
				maxlength="20" required="required"/>
		</dd>

		<dt>
			パスワード<span>Password</span>
		</dt>
		<dd class="required">
			<input type="password" id="password" name="operator_pass" value="<?php echo $info->getPassword(); ?>"
				maxlength="20" required="required"/>
		</dd>

		<dt>
			パスワード<br/>(確認用)<span>Password Confirm</span></dt>
		<dd>
			<?php
				// エラーが有った場合のメッセージ出力場所
				if (isset($errMsgOutput)) {echo $errMsgOutput;}
			?>
			<input type="password" id="password" name="operator_pass_confirm" value="<?php echo $info->getPassConfirm(); ?>"
				maxlength="20" required="required"/>
		</dd>

		<dt>
			コード<span>Code</span>
		</dt>
		<dd>
			<input type="text" id="text" name="operator_code" value="<?php echo $info->getShortName(); ?>"
				maxlength="6" required="required" size="3"/>
			※覚書コードに付与されるオペレーターコード
			<br>
			利用中コード：
			<?= h(implode(" / ", $operatorCodeList)) ?>
		</dd>

		<dt>
			メールアドレス<span>Mail Address</span>
		</dt>
		<dd class="required">
			<input type="email" id="mail_address" name="operator_mail_address" value="<?php echo $info->getMailAddress(); ?>"
				maxlength="50" required="required"/>
		</dd>

		<dt>
			電話番号<span>Phone Number</span>
		</dt>
		<dd>
			<input type="tel" id="phone" name="operator_phone" value="<?php echo $info->getTelHome(); ?>"
				maxlength="20" required="required"/>
		</dd>

		<dt>
			携帯電話番号<span>Mobile Phone Number</span>
		</dt>
		<dd>
			<input type="tel" id="phone" name="operator_mobilephone" value="<?php echo $info->getTelMobile(); ?>"
				maxlength="20" />
		</dd>
	</dl>

	<p id="form_submit" class="center">
		<input type="submit" name="form_submit_button" value="登録する" />
	</p>
	<p id="form_submit" class="right">
		<input type="button" id="form_cancel_button" value="オペレータ管理メニューへ戻る"
			onClick="location.href='61_operator_menu.php'">
	</p>
	<p id="form_submit" class="right">
		<input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>

	<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
</form>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>

</body>
</html>
