<?php
session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (! isset($_SESSION['loginUserID'])) {
    header('Location: 02_logout.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<title>オペレータ管理メニュー</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" href="css/mailform.css" />
<link rel="stylesheet" href="css/thanks.css" />
</head>
<body>

	<div id="thanks">
		<h1>オペレータ管理メニュー</h1>
		<ol type="1">
			<li><a href="62_myinfo_change.php" target="_top">マイ情報修正</a></li>
			<?php if (strcmp($_SESSION['loginUserType'], '02') == 0) { ?>
				<li><a href="63_operator_signup.php" target="_top">オペレータ新規登録</a></li>
				<li><a href="64_operator_list.php" target="_top">オペレータ情報修正</a></li>
			<?php } ?>
		</ol>

		<p class="center">
			<input type="button" id="form_cancel_button"
				value="投資家・投資案件管理メニューへ戻る" onClick="location.href='03_menu.php'">
		</p>
		<p class="center">
			<input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" />
		</p>
	</div>

</body>
</html>
