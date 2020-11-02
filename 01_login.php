<?php

session_start();

require_once 'DSN.php';

// 送信ボタン「form_submit_button」が押された場合
if (isset($_POST['form_submit_button'])) {

	// エラーメッセージ用の変数を初期化
	$errorMessage = '';

	// IDまたはパスワードの入力値が「null」「0」「空白」の場合
	if (empty($_POST['id'])) {
		$errorMessage = 'IDが未入力です。';
	} else if (empty($_POST['password'])) {
		$errorMessage = 'パスワードが未入力です。';
	}

	// IDとパスワードの入力値が「null」「0」「空白」以外の場合
	if (!empty($_POST['id']) && !empty($_POST['password'])) {

		$id = $_POST['id'];
		$password = $_POST['password'];

		try {

			$pdo = db_connect();

			$sql = "select * from OPERATOR where OPERATOR_ID = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([$id]);

			// フォームに入力されたIDを持つユーザーが登録されている場合
			if ($row = $stmt->fetch()) {

				// フォームに入力されたパスワードが、登録されているパスワードと一致(ログイン成功)
			    if (password_verify($password, $row['PASSWORD'])) {

					session_regenerate_id(true);

					$_SESSION['loginUserID'] = $id;
					$_SESSION['loginUserType'] = $row['OPERATOR_TYPE'];
//					$_SESSION['loginUserName'] = $row['OPERATOR_NAME'];

					// 「投資家・投資案件管理メニュー」画面へ移動
					header('Location: 03_menu.php');
					exit();

				// フォームに入力されたパスワードが、登録されているパスワードと不一致(ログイン失敗)
				} else {
					$errorMessage = 'パスワードが間違っています。';
				}

			// 入力されたIDのユーザーが登録されていない場合
			} else {
				$errorMessage = '入力されたIDのユーザーは登録されていません。';
			}

		} catch (PDOException $e) {
			$errorMessage = 'データベース接続・操作エラー';
		}
	}

	$errMsgOutput = '<p class="center add-textDeco">' . htmlspecialchars($errorMessage, ENT_QUOTES) . '</p>';
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
	<meta charset="UTF-8" />
	<title>投資家・投資案件管理ログイン</title>
	<meta name="robots" content="noindex,nofollow" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0" />
	<link rel="stylesheet" href="css/reset.css" />
	<link rel="stylesheet" href="css/style.css" />
	<link rel="stylesheet" href="css/mailform.css" />
	<link rel="stylesheet" href="css/thanks.css" />
	<style type="text/css">
		input[type="submit"] {
			padding: 9px 15px;
			vertical-align: middle;
			line-height: 1;
			background: #5cb85c;
			border: 1px solid #4cae4c;
			border-radius: 3px;
			color: #ffffff;
			font-family: inherit;
			-webkit-appearance: none;
			font-size: 100%;
		}
		.add-textDeco {
			color: red;
			font-size: 2em;
		}
	</style>

</head>
<body>
<div id="main">
	<form action="" method="post" id="mail_form">
		<h1>投資家・投資案件管理ログイン</h1>
			<dl>
				<dt>ID<span>id</span></dt>
				<dd>
					<input type="text" name="id" id="id" name="id"
						value="<?php if (!empty($_POST['id'])) {echo $_POST['id'];} ?>" />
				</dd>
				<dt>パスワード<span>password</span></dt>
				<dd>
					<input type="password" name="password" id="password" name="password"
						value="<?php if (!empty($_POST['password'])) {echo $_POST['password'];} ?>" />
				</dd>
			</dl>
		<?php
			// エラーが有った場合のメッセージ出力場所
			if (isset($_POST['form_submit_button'])) {echo $errMsgOutput;}
		?>
		<p class="center">
			<input type="submit" name="form_submit_button" id="form_submit_button" value="ログイン" />
		</p>
	</form>
</div>
</body>
</html>
