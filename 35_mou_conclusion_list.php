
<?php
session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

require_once 'DSN.php';
require_once 'project_attr.php';
require_once 'security.php';

try {

	// DB接続
	$pdo = db_connect();
	$saiseiList = getPrjAttList($pdo, '01');	// 再生
	$tenbaiList = getPrjAttList($pdo, '02');	// 転売


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
	<title>投資家情報一覧</title>
	<meta name="robots" content="noindex,nofollow" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0" />
	<link rel="stylesheet" href="css/reset.css" />
	<link rel="stylesheet" href="css/style.css" />
	<link rel="stylesheet" href="css/listform.css" />
	<link rel="stylesheet" href="css/thanks.css" />
<style>
.scroll {
	height: 255px;
	width: 100%;
	overflow-y: scroll;
}
.item_name {
		width: 90%;
	    margin: 0 auto;
	    font-size: 135%;
	    font-weight: bold;
	    text-align: center;
}
</style>
</head>
<body>
<div id="list_form">
	<h1>覚書一覧</h1>

	<dl>
		<h3 class="item_name">再生</h3>
		<div class="scroll">
		<?php foreach ($saiseiList as $key => $val) {  ?>
			<dt>
				<?= h($val['attrCode']) ?><br>
				<?= h($val['projectName']. '　｜　'. 'No.'. $val['investorNo']. ': '. $val['lastName']. ' '. $val['firstName']) ?>
				<!--<?= h($val) ?>-->
			</dt>
			<dd>
				<form action="36_mou_conclusion.php" method="post">
					<!--<input type="hidden" name="proj_attr_no" value="<?= h($key) ?>">-->
					<input type="hidden" name="proj_attr_no" value="<?= h($val['projAttrNo']) ?>">
					<input type="button" id="form_submit_button" name="form_submit_button" value="締結" onclick="this.form.submit();"/>
				</form>
			</dd>
		<?php } ?>
		</div>
	</dl>
	<br><br><br>
	<dl>
		<h3 class="item_name">転売</h3>
		<div class="scroll">
		<?php foreach ($tenbaiList as $key => $val) { ?>
			<dt>
				<?= h($val['attrCode']) ?><br>
				<?= h($val['projectName']. '　｜　'. 'No.'. $val['investorNo']. ': '. $val['lastName']. ' '. $val['firstName']) ?>
				<!--<?= h($val) ?>-->
			</dt>
			<dd>
				<form action="36_mou_conclusion.php" method="post">
					<!--<input type="hidden" name="proj_attr_no" value="<?= h($key) ?>">-->
					<input type="hidden" name="proj_attr_no" value="<?= h($val['projAttrNo']) ?>">
					<input type="button" id="form_submit_button" name="form_submit_button" value="締結" onclick="this.form.submit();"/>
				</form>
			</dd>
		<?php } ?>
		</div>
	</dl>

	<p id="form_submit" class="right"><input id="form_cancel_button" type="button" value="覚書管理メニューへ戻る" onClick="location.href='31_mou_menu.php'"></p>
	<p id="form_submit" class="right" ><input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" /></p>
</div>
</body>
</html>
