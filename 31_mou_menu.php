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
<title>覚書管理メニュー</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" href="css/mailform.css" />
<link rel="stylesheet" href="css/thanks.css" />
</head>
<body>

	<div id="thanks">
		<h1>覚書管理メニュー</h1>
		<ol type="1">
			<li><a href="32_mou_signup.php" target="_top">覚書新規登録</a></li>
			<li><a href="33_mou_list.php" target="_top">覚書情報修正</a></li>
			<li><a href="35_mou_conclusion_list.php" target="_top">覚書締結</a></li>
			<li><a href="37_paymentstatus_search.php" target="_top">支払状況確認と入力</a></li>
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
