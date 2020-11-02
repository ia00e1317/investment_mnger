<?php

session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

require_once 'DSN.php';
require_once 'investor.php';
require_once 'project.php';
require_once 'security.php';

try {

	// DB接続
	$pdo = db_connect();

	// 画面表示情報取得
	$investorList = getInvestorList($pdo, array());		// 投資家リスト
	$projectList = getProjectList($pdo);				// 投資案件リスト

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
	<title>支払状況確認</title>
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
	<h1>支払状況確認</h1>
	<dl>
		<dt>投資家名<span>Investor Name</span></dt>
		<dd>
			<form action="38_investor_search_result.php" method="post">
				<select id="product" name="investor_no" required="required">
					<option value="">選択してください</option>
					<?php foreach ($investorList as $key => $val) { ?>
						<option value="<?= h($key) ?>"><?= h($val) ?></option>
					<?php } ?>
				</select>
				<input type="month" name="investor_date" required="required">
				<input type="submit" id="form_submit_button" value="検索" />
			</form>
		</dd>

		<dt>投資案件名<span>project name</span></dt>
		<dd>
			<form action="39_project_search_result.php" method="post">
				<select id="product" name="project_no" required="required">
					<option value="">選択してください</option>
					<?php foreach ($projectList as $key => $val) { ?>
						<option value="<?= h($key) ?>"><?= h($val) ?></option>
					<?php } ?>
		        </select>
				<input type="month" name="project_date" required="required">
				<input type="submit" id="form_submit_button" value="検索" />
			</form>
		</dd>
	</dl>

	<p id="form_submit" class="right">
		<input type="button" id="form_cancel_button" value="覚書管理メニューへ戻る" onClick="location.href='31_mou_menu.php'">
	</p>
	<p id="form_submit" class="right" >
		<input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>

</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>

</body>
</html>
