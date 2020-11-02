<?php

session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
	<meta charset="UTF-8" />
	<title>投資家・投資案件管理メニュー</title>
	<meta name="robots" content="noindex,nofollow" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0" />
	<link rel="stylesheet" href="css/reset.css" />
	<link rel="stylesheet" href="css/style.css" />
	<link rel="stylesheet" href="css/mailform.css" />
	<link rel="stylesheet" href="css/thanks.css" />
</head>
<body>
<div id="thanks">
	<h1>投資家・投資案件管理メニュー</h1>
	<ol type="1">
		<li><a href="11_investor_menu.php" target="_top">投資家管理</a></li>
		<li><a href="21_project_menu.php" target="_top">投資案件管理</a></li>
		<li><a href="31_mou_menu.php" target="_top">覚書管理</a></li>
		<li><a href="41_download.php" target="_top">個別配当表出力</a></li>
		<li><a href="51_download.php" target="_top">投資家相関図出力</a></li>
		<li><a href="61_operator_menu.php" target="_top">オペレータ管理</a></li>
	</ol>
	<p class="center">
		<input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>
</div>
</body>
</html>
