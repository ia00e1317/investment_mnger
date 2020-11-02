<?php

session_start();

// メッセージ取得
$message = '';
if (isset($_SESSION['loginUserID'])) {
	$message = 'ログアウトしました。';
} else {
	$message = 'セッションがタイムアウトしました。';
}

// セッション変数を初期化、及びセッションのデータを破棄
$_SESSION = [];
@session_destroy();

// メッセージをHTML出力化
$msgOutput = '<p class="center">' . htmlspecialchars($message, ENT_QUOTES) . '</p>';

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
	<meta charset="UTF-8" />
	<title>ログアウト画面</title>
	<meta name="robots" content="noindex,nofollow" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0" />
	<link rel="stylesheet" href="css/reset.css" />
	<link rel="stylesheet" href="css/style.css" />
	<link rel="stylesheet" href="css/mailform.css" />
	<link rel="stylesheet" href="css/thanks.css" />
</head>
<body>
<div id="thanks">
	<h3>ログアウト画面</h3>
	<?= $msgOutput; ?>
	<p class="center">
		<input type="button" id="form_submit_button" value="ログイン画面へ戻る"  onClick="location.href='01_login.php'" >
	</p>
</div>
</body>
</html>
