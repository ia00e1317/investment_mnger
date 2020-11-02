<?php

session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

require_once 'DSN.php';
require_once 'investor.php';
require_once 'security.php';

ob_start();

try {

	// DB接続
	$pdo = db_connect();

	// 「修正する」ボタン押下
	if (isset($_POST['form_submit_button'])) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// 入力値取得
		$info = new investor();
		$info->setNo($_SESSION['investor_no']);
		$info->setType($_POST['type']);
		$info->setLastName($_POST['name_1']);
		$info->setFirstName($_POST['name_2']);
		$info->setLastNameKana($_POST['read_1']);
		$info->setFirstNameKana($_POST['read_2']);
		$info->setMailAddress($_POST['mail_address']);
		$info->setPostCode($_POST['postal']);
		$info->setAddress($_POST['address']);
		$info->setTel($_POST['phone']);
		$info->setIntroducedInvestor($_POST['introducing_investor']);
		$info->setBankName($_POST['bank_name']);
		$info->setBranchNo($_POST['branch_number']);
		$info->setAccountType($_POST['type_of_account']);
		$info->setAccountNo($_POST['account_number']);
		$info->setAccountName($_POST['account_holder']);
		$info->setIntroducerDistribution($_POST['distribution']);

		// 修正実行
		updateInvestor($pdo, $info);

		// セッションクリア
		unset($_SESSION['investor_no']);

		// 「投資家情報一覧(修正)」画面へ移動
		header('Location: 13_investor_list.php');
		exit();

	// 初期表示
	} else {

		// 投資家情報取得
		$info = getInvestorInfo($pdo, $_POST['investor_no']);

		// 投資家リスト取得
		$investorList = getInvestorList($pdo, array($_POST['investor_no']));

		// セッション登録
		$_SESSION['investor_no'] = $_POST['investor_no'];
	}

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
	<title>投資家情報修正</title>
	<meta name="robots" content="noindex,nofollow" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0" />
	<link rel="stylesheet" href="css/reset.css" />
	<link rel="stylesheet" href="css/style.css" />
	<link rel="stylesheet" href="css/mailform.css" />
	<link rel="stylesheet" href="css/thanks.css" />
	<style type="text/css">
		input[name="distribution"] {
			width: 10%;
		}
	</style>

</head>
<body onload="calOutput();">
<div id="main">
<form action="" method="post" id="mail_form">
	<h1>投資家情報修正</h1>
	<dl>
		<dt>種別<span>type</span></dt>
		<dd>
			<ul>
				<li><label> <input type="radio" class="type" name="type" value="01"
					<?php if (strcmp($info->getType(), '01') == 0) {echo 'checked';} ?> />一般</label></li>
				<li><label> <input type="radio" class="type" name="type" value="02"
					<?php if (strcmp($info->getType(), '02') == 0) {echo 'checked';} ?> />特別</label></li>
			</ul>
		</dd>

		<dt>投資家名<span>Investor Name</span></dt>
		<dd class="required">
			<input type="text" id="name_1" name="name_1" value="<?= h($info->getLastName()) ?>"
				placeholder="山田" maxlength="20" required="required"/>
			<input type="text" id="name_2" name="name_2" value="<?= h($info->getFirstName()) ?>"
				placeholder="太郎" maxlength="20" required="required"/>
		</dd>

		<dt>ふりがな<span>Name Reading</span></dt>
		<dd>
			<input type="text" id="read_1" name="read_1" value="<?= h($info->getLastNameKana()) ?>"
				placeholder="やまだ" maxlength="20" required="required"/>
			<input type="text" id="read_2" name="read_2"  value="<?= h($info->getFirstNameKana()) ?>"
				placeholder="たろう" maxlength="20" required="required"/>
		</dd>

		<dt>個人ナンバー<span>personal number</span></dt>
		<dd>
			<?= h($info->getNo()) ?>
		</dd>

		<dt>メールアドレス<span>Mail Address</span></dt>
		<dd class="required">
			<input type="email" id="mail_address" name="mail_address" value="<?= h($info->getMailAddress()) ?>"
				placeholder="example@xxx.com" maxlength="100" required="required"/>
		</dd>

		<dt>郵便番号<span>Postal</span></dt>
		<dd class="required">
			<input type="number" id="postal" name="postal" value="<?= h($info->getPostCode()) ?>"
				placeholder="1500002" onkeyup="AjaxZip3.zip2addr(this,'','address','address');" maxlength="10" required="required"/>
			<a href="http://www.post.japanpost.jp/zipcode/" target="_blank">郵便番号検索</a>
		</dd>

		<dt>住所<span>Address</span></dt>
		<dd>
			<input type="text" id="address" name="address" placeholder="東京都〇〇区〇〇町1-1-1 〇〇マンション 101号室"
				value="<?= h($info->getAddress()) ?>" required="required"/>
		</dd>

		<dt>電話番号<span>Phone Number</span></dt>
		<dd>
			<input type="tel" id="phone" name="phone" value="<?= h($info->getTel()) ?>"
				placeholder="090-1234-5678" maxlength="20" required="required" />
		</dd>

		<dt>紹介投資家<span>Introducing investor</span></dt>
		<dd>
			<select id="introducing_investor" name="introducing_investor">
				<option value="" >選択してください</option>
				<?php foreach ($investorList as $key => $val) { ?>
					<option value="<?= h($key) ?>" <?php if (strcmp($info->getIntroducedInvestor(), $key) == 0) { echo 'selected';} ?>>
						<?= h($val) ?>
					</option>
				<?php } ?>
			</select>
		</dd>

		<dt>銀行名<span>Bank name</span></dt>
		<dd>
			<input type="text" id="bank_name" name="bank_name" value="<?= h($info->getBankName()) ?>"
				placeholder="大人銀行"  maxlength="20" required="required" />
		</dd>

		<dt>支店番号<span>branch number</span></dt>
		<dd>
			<input type="number" id="branch_number" name="branch_number" placeholder="123"
				value="<?= h($info->getBranchNo()) ?>" maxlength="10" required="required" />
		</dd>

		<dt>口座種別<span>type of account </span></dt>
		<dd>
			<ul>
				<li>
					<label>
						<input type="radio" class="type_of_account" name="type_of_account" value="普通"
							<?php if (strcmp($info->getAccountType(), '普通') == 0) {echo 'checked';} ?> />普通
					</label>
				</li>
				<li>
					<label>
						<input type="radio" class="type_of_account" name="type_of_account" value="当座"
							<?php if (strcmp($info->getAccountType(), '当座') == 0) {echo 'checked';} ?> />当座
					</label>
				</li>
			</ul>
		</dd>

		<dt>口座番号<span>account number</span></dt>
		<dd>
			<input type="text" id="account_number" name="account_number" placeholder="1234567"
				value="<?= h($info->getAccountNo()) ?>" maxlength="10" required="required" />
		</dd>

		<dt>口座名義<span>account holder</span></dt>
		<dd>
			<input type="text" id="account_holder" name="account_holder" placeholder="TARO YAMADA"
				value="<?= h($info->getAccountName()) ?>" required="required" />
		</dd>

		<dt>エージェントフィー割合<span>agent fee percentage</span></dt>
		<dd>
			<input type="number" min="0" max="99.9" step="0.1" style="width:13%;" id="distribution" name="distribution"
				value="<?= h($info->getIntroducerDistribution()) ?>" required="required"/> ％
		</dd>
	</dl>

	<p id="form_submit" class="center">
		<input type="submit" name="form_submit_button" value="修正する" />
	</p>
	<p id="form_submit" class="right">
		<input id="form_cancel_button" type="button" value="戻る" onClick="location.href='13_investor_list.php'">
	</p>
	<p id="form_submit" class="right" >
		<input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>

	<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
</form>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>

<!-- フリガナ自動入力ライブラリここから -->
<script src="js/jquery.autoKana.js"></script>
<script>
	(function( $ ) {
		$.fn.autoKana( '#name_1', '#read_1', {
			katakana: false
		});
		$.fn.autoKana( '#name_2', '#read_2', {
			katakana: false
		});
	})( jQuery );
</script>
<!-- フリガナ自動入力ライブラリここまで -->

<!-- 住所自動入力ライブラリここから -->
<script src="js/ajaxzip3.js"></script>
<!-- 住所自動入力ライブラリここまで -->

</body>
</html>
