<?php

session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

require_once 'DSN.php';
require_once 'operator.php';
require_once 'security.php';

try {

	// オペレータ一覧取得
	$pdo = db_connect();
	$list = getOperatorList($pdo);

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
	<title>オペレータ一覧</title>
	<meta name="robots" content="noindex,nofollow" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0" />
	<link rel="stylesheet" href="css/reset.css" />
	<link rel="stylesheet" href="css/style.css" />
	<link rel="stylesheet" href="css/listform.css" />
	<link rel="stylesheet" href="css/thanks.css" />
</head>
<body>
<div id="list_form">
	<h1>オペレータ情報一覧</h1>

	<dl>
		<?php foreach ($list as $key => $val) { ?>
			<dt><?= h($val) ?></dt>
			<dd>
				<form action="65_operator_change.php" method="post">
					<input type="hidden" name="operator_id" value="<?= h($key) ?>">
					<input type="button" name="form_submit_button" value="修正" onclick="this.form.submit();" />
				</form>
			</dd>
		<?php } ?>
	</dl>

	<p id="form_submit" class="right"><input id="form_cancel_button" type="button" value="オペレータ管理メニューへ戻る" onClick="location.href='61_operator_menu.php'"></p>
	<p id="form_submit" class="right" ><input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" /></p>
</div>
</body>
</html>
