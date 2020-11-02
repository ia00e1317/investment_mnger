<?php
session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

require_once 'DSN.php';
require_once 'security.php';
require_once 'category_signup.php';

ob_start();

try {

	// DB接続
	$pdo = db_connect();

	// エラーメッセージ用の変数を初期化
	$errorMessage = '';

	// 追加ボタン押下時
	if (isset($_POST['form_submit_button'])) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// 入力値取得
		$info = new category_signup();
		$info->setName($_POST['category_name']);
		$info->setCatName($_POST['cat_name']);

		// 未入力の場合
		//if ( empty($_POST['category_name']) || empty($_POST['cat_name']) ) {
		if ( strcmp($_POST['category_name'], '') == 0 || strcmp($_POST['cat_name'], '') == 0 ) {
			// エラーメッセージ設定
			$errorMessage = '追加するには「カテゴリー名」「省略名」両方に入力してください。';
			$errMsgOutput = '<p style="color:red; margin-top: 0;" class="center">' . htmlspecialchars($errorMessage, ENT_QUOTES) . '</p>';
		}else{
			// 追加実行
			insertCategory($pdo, $info);

			// 同一ページ再表示
			header ( 'Location: 25_project_category_edit.php' );
			exit();
		}

	// 削除ボタン押下
	//} else if (isset($_POST['category_id'])) {
	} else if (isset($_POST['form_delete_button'])) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// 削除実行
		hideCategory($pdo, (int)$_POST ['category_id'], 1);

		// 同一ページ再表示
		header ( 'Location: 25_project_category_edit.php' );
		exit();

	// 初期表示
	} else {

		// 初期表示
		$info = new category_signup();
	}

	// カテゴリー情報取得
	$categoryDefList = getCategoryDefList($pdo);

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
<title>カテゴリー編集画面</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" href="css/mailform.css" />
<link rel="stylesheet" href="css/table.css" />
<link rel="stylesheet" href="css/maildiv.css" />
<style>
	table.cate_list { width:80%; margin-top:2em; }
	table.cate_list .cate_id { width:9%; }
	table.cate_list .cate_catname { width:14%; }
	table.cate_list .cate_button { width:9%; }
	table.cate_list th,
	table.cate_list td {
		padding: 0.4em 0.1em 0.4em 0.1em;
		border-right: none;
		text-align:center;
	}
	table.cate_list th { font-weight: bold;	}

	@media screen and (max-width: 600px) {
		table.cate_list .cate_id { width:100%; }
		table.cate_list .cate_catname { width:100%; }
		table.cate_list .cate_button { width:100%; }
	}
</style>
</head>
<body>
<div id="main">
	<form action="" method="post" id="mail_form">
		<h1>カテゴリー追加</h1>
		<table class="cate_list">
			<tr>
				<th class="cate_id ">ID</th>
				<th class="cate_name">カテゴリー名</th>
				<th class="cate_catname">省略名</th>
				<th class="cate_button"></th>
			</tr>
			<tr>
				<td><input type ="text" id="dummy_ID" name="dummy_ID" maxlength="3" size="2" value="" disabled style="background-color:silver;"></td>
				<td><input type ="text" id="category_name" name="category_name" maxlength="30" size="60" value="<?= h($info->getName()) ?>"></td>
				<td><input type ="text" id="cat_name" name="cat_name" maxlength="3" size="6" value="<?= h($info->getCatName()) ?>"></td>
				<td style="vertical-align: bottom;"><input class="right" type="submit" value="追加" name="form_submit_button" id="form_submit_button"></td>
			</tr>
		</table>
		<?php
			// エラーが有った場合のメッセージ出力場所
			if (isset($errMsgOutput)) {echo $errMsgOutput;}
		?>
		<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
	</form>
	<div id="mail_form">
		<h1>登録済みカテゴリー</h1>
		<table class="cate_list">
		<tr>
			<th class="cate_id">ID</th>
			<th class="cate_name">カテゴリー名</th>
			<th class="cate_catname">省略名</th>
			<th class="cate_button"></th>
		</tr>
		<?php foreach ($categoryDefList as $val) { ?>
		<tr>
			<td><?= h($val['categoryId']) ?></td>
			<td><?= h($val['categoryName']) ?></td>
			<td><?= h($val['catName']) ?></td>
			<td>
				<form action="" method="post" name="delete">
					<input type="hidden" name="category_id" value="<?= $val['categoryId'] ?>">
					<input type="submit" name="form_delete_button" value="削除">
					<!--<input type="button" name="form_delete_button" value="削除" onclick="this.form.submit();"/>-->
					<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
				</form>
			</td>	
		</tr>
		<?php } ?>
		</table>
	</div>
	<form action="" method="post" id="mail_form">
		<p id="form_submit" class="right" style="border-top-style:none;">
			<input type="button" id="form_cancel_button" value="投資案件管理メニューへ戻る"
				onClick="location.href='21_project_menu.php'">
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
