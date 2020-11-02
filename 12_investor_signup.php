<?php

session_start ();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (! isset ( $_SESSION ['loginUserID'] )) {
	header ( 'Location: 02_logout.php' );
	exit ();
}

require_once 'DSN.php';
require_once 'investor.php';
require_once 'security.php';

ob_start();

try {

	$pdo = db_connect();

	// 送信ボタン「form_submit_button」が押された際の処理
	if (isset ( $_POST ['form_submit_button'] )) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// 入力値取得
		$info = new investor();
		//$info->setNo($_POST ['personal_number']);
		$info->setNo(str_pad($_POST ['personal_number'], 3, 0, STR_PAD_LEFT));
		$info->setType($_POST ['type']);
		$info->setLastName($_POST ['name_1']);
		$info->setFirstName($_POST ['name_2']);
		$info->setLastNameKana($_POST ['read_1']);
		$info->setFirstNameKana($_POST ['read_2']);
		$info->setMailAddress($_POST ['mail_address']);
		$info->setPostCode($_POST ['postal']);
		$info->setAddress($_POST ['address']);
		$info->setTel($_POST ['phone']);
		$info->setIntroducedInvestor($_POST ['introducing_investor']);
		$info->setBankName($_POST ['bank_name']);
		$info->setBranchNo($_POST ['branch_number']);
		$info->setAccountType($_POST ['type_of_account']);
		$info->setAccountNo($_POST ['account_number']);
		$info->setAccountName($_POST ['account_holder']);
		$info->setIntroducerDistribution($_POST ['distribution']);

		// 個人Noの重複チェック
		if (!isValidNo($pdo, $info->getNo())) {

			// エラーメッセージ設定
			$errorMessage = '個人ナンバーが重複しています。';
			$errMsgOutput = '<p style="color:red; margin-top: 0;">' . htmlspecialchars($errorMessage, ENT_QUOTES) . '</p>';

		} else {

			// 新規登録
			insertInvestor($pdo, $info);

			// 「投資家管理メニュー」画面へ移動
			header('Location: 11_investor_menu.php');
			exit();
		}

	// 初期表示
	} else {

		// 投資家情報取得
		$info = new investor();
	}

	// 投資家リスト取得
	$investorList = getInvestorList($pdo, array());

	// CSRF対策用トークン取得
	$csrf_token = setCSRFtoken();

} catch (PDOException $e) {
	echo "<script type=\"text/javascript\">alert(\'データベース接続・操作処理エラー\');</script>";
} finally {
	$pdo = null;
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<title>投資家新規登録</title>
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
<body>
<div id="main">
<form action="" method="post" id="mail_form">
	<h1>投資家新規登録</h1>
	<dl>
		<dt>種別<span>type</span></dt>
		<dd>
			<ul>
				<li><label> <input type="radio" class="type" name="type" value="01"
					<?php if (strcmp($info->getType(), '01') == 0) { echo 'checked'; } ?> />一般</label></li>
				<li><label> <input type="radio" class="type" name="type"value="02"
					<?php if (strcmp($info->getType(), '02') == 0) { echo 'checked'; } ?> />特別</label></li>
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
			<input type="text" id="read_2" name="read_2" value="<?= h($info->getFirstNameKana()) ?>"
				placeholder="たろう" maxlength="20" required="required"/>
		</dd>

		<dt>個人ナンバー<span>personal number</span></dt>
		<dd>
			<?php
				// エラーが有った場合のメッセージ出力場所
				if (isset($errMsgOutput)) { echo $errMsgOutput; }
			?>
			<input type="number" id="personal_number" name="personal_number"
				value="<?= h($info->getNo()) ?>" placeholder="111" min="1" max="999" step="1" required="required"/>
				<!--value="<?= h($info->getNo()) ?>" placeholder="11111" min="1" max="99999" step="1" required="required"/>-->
		</dd>

		<dt>メールアドレス<span>Mail Address</span></dt>
		<dd class="required">
			<input type="email" id="mail_address" name="mail_address" value="<?= h($info->getMailAddress()) ?>"
				placeholder="example@xxx.com" maxlength="100" required="required"/>
		</dd>

		<dt>郵便番号<span>Postal</span></dt>
		<dd class="required">
			<input type="number" id="postal" name="postal" value="<?= h($info->getPostCode()) ?>"
				placeholder="1500001" onkeyup="AjaxZip3.zip2addr(this,'','address','address');" maxlength="10" required="required"/>
			<a href="http://www.post.japanpost.jp/zipcode/" target="_blank">郵便番号検索</a>
		</dd>

		<dt>住所<span>Address</span></dt>
		<dd>
			<input type="text" id="address" name="address" value="<?= h($info->getAddress()) ?>"
				placeholder="東京都〇〇区〇〇町1-1-1 〇〇マンション 101号室" required="required"/>
		</dd>

		<dt>電話番号<span>Phone Number</span></dt>
		<dd>
			<input type="tel" id="phone" name="phone" value="<?= h($info->getTel()) ?>"
				placeholder="090-1234-5678" maxlength="20" required="required" />
		</dd>

		<dt>紹介投資家<span>Introducing investor</span></dt>
		<dd>
			<select id="introducing_investor" name="introducing_investor">
				<option value="">選択してください</option>
				<?php foreach ($investorList as $key => $val) { ?>
					<option value="<?= $key ?>"
					<?php if (strcmp($info->getIntroducedInvestor(), $key) == 0) { echo 'selected'; } ?> >
						<?= $val ?>
					</option>
				<?php } ?>
			</select>
		</dd>

		<dt>銀行名<span>Bank name</span></dt>
		<dd>
			<input type="text" id="bank_name" name="bank_name" value="<?= h($info->getBankName()) ?>"
				placeholder="大人銀行" maxlength="20" required="required" />
		</dd>

		<dt>支店番号<span>branch number</span></dt>
		<dd>
			<input type="number" id="branch_number" name="branch_number" placeholder="123"
				value="<?= h($info->getBranchNo()) ?>" maxlength="10" required="required" />
		</dd>

		<dt>口座種別<span>type of account </span></dt>
		<dd>
			<ul>
				<li><label> <input type="radio" class="type_of_account" name="type_of_account"
					value="普通" <?php if (strcmp($info->getAccountType(), '普通') == 0) { echo 'checked'; } ?> />普通
				</label></li>
				<li><label> <input type="radio" class="type_of_account" name="type_of_account"
					value="当座" <?php if (strcmp($info->getAccountType(), '当座') == 0) { echo 'checked'; } ?> />当座
				</label></li>
			</ul>
		</dd>

		<dt>口座番号<span>account number</span></dt>
		<dd>
			<input type="text" id="account_number" name="account_number" placeholder="1234567"
				value="<?= h($info->getAccountNo()) ?>" maxlength="10" required="required" />
		</dd>

		<dt>口座名義<span>account holder</span></dt>
		<dd>
			<input type="text" id="account_holder" name="account_holder"
				value="<?= h($info->getAccountName()) ?>" placeholder="TARO YAMADA" maxlength="20" required="required" />
		</dd>

		<dt>エージェントフィー割合<span>agent fee percentage</span></dt>
		<dd>
			<input type="number" min="0" max="99.9" step="0.1" style="width:13%" id="distribution" name="distribution"
				value="<?= h($info->getIntroducerDistribution()) ?>" placeholder="2.1" required="required" /> ％
		</dd>
	</dl>

	<p id="form_submit" class="center">
		<input type="submit" name="form_submit_button" value="登録する" />
	</p>
	<p id="form_submit" class="right">
		<input type="button" id="form_cancel_button" value="投資家管理メニューへ戻る"
			onClick="location.href='11_investor_menu.php'">
	</p>
	<p id="form_submit" class="right">
		<input type="button" id="form_logout_button" value="ログアウト"
			onClick="location.href='02_logout.php'" />
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
