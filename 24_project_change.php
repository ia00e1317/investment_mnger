<?php
session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

require_once 'DSN.php';
require_once 'project.php';
require_once 'security.php';
require_once 'payment_def.php';

ob_start();

try {

	// DB接続
	$pdo = db_connect();

	// 「変更」ボタン押下時
	if (isset($_POST['form_submit_button'])) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		$info = new project();
		$info->setNo($_SESSION['project_no']);
		$info->setType($_POST['investment_type']);
		$info->setCategoryNumber((int)$_POST ['category_no']);
		//$info->setCategoryName($_POST ['category_name']);
		$info->setMemorandum($_POST ['memorandum']);
		$info->setName($_POST['project_name']);
		$info->setStartDate($_POST['start_date']);
		$info->setEndDate($_POST['end_date']);
		$info->setDividendMonth($_POST ['dividend_month']);
		$info->setDividendDate($_POST ['dividend_date']);
		$info->setDividendCount($_POST ['dividend_count']);
		$info->setDividendRate($_POST ['dividend_rate']);
		$info->setRepaymentCount($_POST ['repayment_count']);
		$info->setWaitPeriod($_POST ['wait_period']);

		// 相関チェック
		$error = $info->validation();

		// エラー無し
		if (count($error) < 1) {

			// トランザクション開始
			$pdo->beginTransaction();

			// 更新実行
			updateProject($pdo, $info);

			// 配当更新
			for ($i = 1 ; $i < count($_POST['haito_trans_no']); $i++) {

				$haito = new payment_def();
				$haito->setProjectNo($_SESSION['project_no']);
				$haito->setTransNo($_POST['haito_trans_no'][$i]);
				$haito->setType('01');
				$haito->setPlannedDate($_POST['haito_dividend_date'][$i]);
				$haito->setCommission($_POST['haito_fee'][$i]);

				if (strcmp($_POST['haito_delFlg'][$i], '1') == 0) {
					// DELETE
					deletePaymentDef($pdo, $haito->getTransNo());
				} else if (strcmp($_POST['haito_trans_no'][$i], '') == 0) {
					// INSERT
					insertPaymentDef($pdo, $haito);
				} else {
					// UPDATE
					updatePaymentDef($pdo, $haito);
				}
			}

			// 元本更新
			for ($i = 1 ; $i < count($_POST['ganpon_trans_no']); $i++) {

				$ganpon = new payment_def();
				$ganpon->setProjectNo($_SESSION['project_no']);
				$ganpon->setTransNo($_POST['ganpon_trans_no'][$i]);
				$ganpon->setType('03');
				$ganpon->setPlannedDate($_POST['ganpon_dividend_date'][$i]);
				$ganpon->setCommission($_POST['ganpon_fee'][$i]);

				if (strcmp($_POST['ganpon_delFlg'][$i], '1') == 0) {
					// DELETE
					deletePaymentDef($pdo, $ganpon->getTransNo());
				} else if (strcmp($_POST['ganpon_trans_no'][$i], '') == 0) {
					// INSERT
					insertPaymentDef($pdo, $ganpon);
				} else {
					// UPDATE
					updatePaymentDef($pdo, $ganpon);
				}
			}

			// オプション更新
			for($i = 1 ; $i < count($_POST['option_dividend_date']); $i++) {

				$option = new payment_def();
				$option->setProjectNo($_SESSION['project_no']);
				$option->setTransNo($_POST['option_trans_no'][$i]);
				$option->setType('02');
				$option->setTermFrom($_POST['option_from'][$i]);
				$option->setTermTo($_POST['option_to'][$i]);
				$option->setPlannedDate($_POST['option_dividend_date'][$i]);
				$option->setCommission($_POST['option_fee'][$i]);
				$option->setPrice($_POST['stock_price'][$i]);
				$option->setMemo($_POST['option_memo'][$i]);

				if (strcmp($_POST['option_delFlg'][$i], '1') == 0) {
					// DELETE
					deletePaymentDef($pdo, $option->getTransNo());
				} else if (strcmp($_POST['option_trans_no'][$i], '') == 0) {
					// INSERT
					insertPaymentDef($pdo, $option);
				} else {
					// UPDATE
					updatePaymentDef($pdo, $option);
				}
			}

			// コミット
			$pdo->commit();

			// 後処理
			unset($_SESSION['project_no']);

			// 「投資案件リスト」画面へ移動
			header ( 'Location: 23_project_list.php' );
			exit ();
		}

		//Validationエラー時の復帰
		$haitoList = getPaymentDefList($pdo, $_SESSION['project_no'], '01');	// 配当
		$ganponList = getPaymentDefList($pdo, $_SESSION['project_no'], '03');	// 元本
		$optionList = getPaymentDefList($pdo, $_SESSION['project_no'], '02');	// オプション

	// 初期表示
	} else {

		// 投資案件情報取得
		$info = getProjectInfo($pdo, $_POST ['project_no']);
		$haitoList = getPaymentDefList($pdo, $_POST['project_no'], '01');	// 配当
		$ganponList = getPaymentDefList($pdo, $_POST['project_no'], '03');	// 元本
		$optionList = getPaymentDefList($pdo, $_POST['project_no'], '02');	// オプション

		// 投資案件Noをセッションに登録
		$_SESSION['project_no'] = $_POST ['project_no'];
	}

	//カテゴリー選択肢取得
	$categoryOptionList = getCategoryOptionList($pdo);

	// CSRF対策用トークン取得
	$csrf_token = setCSRFtoken();

} catch (PDOException $e) {
	echo '<script type="text/javascript">alert("データベース接続・操作処理エラー")</script>';
} finally {
	
	try {
		$pdo->rollBack();
	} catch (PDOException $e) {
		// TODO
	}
	$pdo = null;
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<title>投資案件情報修正</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" href="css/mailform.css" />
<link rel="stylesheet" href="css/thanks.css" />
</head>
<body>
<div id="main">
<form action="" method="post" id="mail_form">
	<h1>投資案件情報修正</h1>
	<dl>
		<dt>種別<span>Investment Type</span></dt>
		<dd>
			<ul>
			<li>
				<label><input type="radio" name="investment_type" class="investment_type" value="01"
						<?php if (strcmp($info->getType(), '01') == 0) { echo 'checked'; } ?> />再生</label>
				</li>
				<li>
					<label><input type="radio" name="investment_type" class="investment_type" value="02"
						<?php if (strcmp($info->getType(), '02') == 0) { echo 'checked'; } ?> />転売</label>
				</li>

			</ul>
		</dd>

		<dt>カテゴリー<span>category</span></dt>
		<dd>
			<!--<label>既存<br></label>-->
			<select id="category_no" name="category_no" required="required">
				<option value="">選択してください</option>
				<?php foreach ($categoryOptionList as $key => $val) { ?>
					<option value="<?= $key ?>"
						<?php if (strcmp($info->getCategoryNumber(), $key) == 0) { echo 'selected'; } ?> >
							<?= $val ?>
					</option>
				<?php } ?>
			</select>
			<textarea id="memorandum" name="memorandum" cols="100" rows="10" maxlength="1000" style="max-width: 90%; height: 2em; padding: 2px 2%;
			border: 1px solid #cccccc; border-radius: 3px; background: #fafafa; -webkit-appearance: none; font-size: 100%;
			font-family: inherit; margin-top: 7px; height: 5em"><?= h($info->getMemorandum()) ?></textarea><!-- required-->
		</dd>
		<dt>案件名<span>project name</span></dt>
		<dd>
			<input type="text" id="project_name" name="project_name" value="<?= h($info->getName()) ?>"
				maxlength="40" required="required"/>
		</dd>
		<dt>覚書発行日<span>start date</span></dt>
		<dd>
			<input type="date" id="start_date" name="start_date" value="<?= h($info->getStartDate()) ?>" required="required"/>
		</dd>
		<dt>案件終了日<span>end date</span></dt>
		<dd>
			<?php
				// エラーが有った場合のメッセージ出力場所
				if (isset($error['endDate'])) { echo $error['endDate']; }
			?>
			<input type="date" id="end_date" name="end_date" value="<?= h($info->getEndDate()) ?>" required="required"/>
		</dd>
	</dl>

	<h2>配当日指定</h2>
	<div id配当回数="haitoRule">
		<dl>
			<dt>配当日　＆　配当回数<span>dividend date & count</span></dt>
			<dd>
				<input type="number" id="dividend_month" name="dividend_month" value="<?= h($info->getDividendMonth()) ?>"
					min="1" max="12" step="1" required="required" style="width: 13%;">&nbsp;ヶ月毎&nbsp;
				<input type="number" id="dividend_date" name="dividend_date" value="<?= h($info->getDividendDate()) ?>"
					misn="0" max="31" step="1" required="required" style="width: 13%;">&nbsp;日
				<input type="number" id="dividend_count" name="dividend_count" value="<?= h($info->getDividendCount()) ?>"
						min="0" max="100" step="1" required="required" style="width: 13%;"> &nbsp;回 <!-- 配当回数入力欄  -->
			</dd>
			<dt>配当率<span>dividend rate</span></dt>
			<dd>
				<input type="number" id="dividend_rate" name="dividend_rate" value="<?= h($info->getDividendRate()) ?>"
					style="width:13%" min="0" max="99.9" step="0.1" required="required"/>&nbsp;％
			</dd>
			<dt>元本償還回数<span>repayment count</span></dt>
			<dd>
				<input type="number" id="repayment_count" name="repayment_count" value="<?= h($info->getRepaymentCount()) ?>"
					style="width:13%" min="0" max="100" step="1" required="required"/>&nbsp;回
			</dd>
			<dt>待機期間<span>waiting period</span></dt>
			<dd>
				<input type="number" id="wait_period" name="wait_period" value="<?= h($info->getWaitPeriod()) ?>"
					style="width:13%" min="0" max="99" step="1" required="required"/>&nbsp;ヶ月
			</dd>
		</dl>
		<p id="form_submit" class="center">
			<input type="button" name="createHaito" id="createHaito" value="配当日作成"/><!-- onclick="setData();"-->
		</p>
	</div>

	<h2>配当日</h2>
	<dl>
		<dt>配当<span>dividend</span><input type="button" id="haito_add_button" value="配当日追加" /></dt>
		<dd class="required">
			<table id="haito">
				<tbody>
				    <tr style="display: none;"><td>
						<input type="hidden" name="haito_delFlg[]" value="">
						<input type="hidden" name="haito_trans_no[]" value="">
						<span class="incNum"></span>回目
						配当日<input type="date" id="date" name="haito_dividend_date[]" value=""/>
						配当率<input type="number" min="0" max="99.9" step="0.1" class="haito_fee" name="haito_fee[]" style="width: 14%;" value=""/> ％
						<ul id="thanks1">
							<li><input type="button" class="haito_delete_button_new" value="削除" /></li>
						</ul>
					</td></tr>
					<?php foreach ($haitoList as $val) { ?>
						<tr><td>
							<input type="hidden" name="haito_delFlg[]" value="">
							<input type="hidden" name="haito_trans_no[]" value="<?= h($val['transNo']) ?>">
							<span class="incNum"></span>回目
							配当日<input type="date" id="date" name="haito_dividend_date[]" value="<?= h($val['plannedDate']) ?>" required="required"/>
							配当率<input type="number" min="0" max="99.9" step="0.1" class="haito_fee" name="haito_fee[]"
								style="width: 14%;" value="<?= h($val['commission']) ?>" required="required"/> ％
							<ul id="thanks1">
								<li><input type="button" class="haito_delete_button" value="削除" /></li>
							</ul>
						</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</dd>

		<dt>元本償還<span>principal repayment</span><input type="button" id="ganpon_add_button" value="元本償還追加" /></dt>
		<dd class="required">
			<table id="ganpon">
				<tbody>
				    <tr style="display: none;"><td>
						<input type="hidden" name="ganpon_delFlg[]" value="">
						<input type="hidden" name="ganpon_trans_no[]" value="">
						<span class="incNum"></span>回目
						償還日<input type="date" id="date" name="ganpon_dividend_date[]" value=""/>
						元本償還率<input type="number" min="0" max="100.0" step="0.1" class="ganpon_fee" name="ganpon_fee[]" style="width: 14%;" value=""/> ％
						<ul id="thanks1">
							<li><input type="button" class="ganpon_delete_button_new" value="削除" /></li>
						</ul>
					</td></tr>
					<?php foreach ($ganponList as $val) { ?>
						<tr><td>
							<input type="hidden" name="ganpon_delFlg[]" value="">
							<input type="hidden" name="ganpon_trans_no[]" value="<?= h($val['transNo']) ?>">
							<span class="incNum"></span>回目
							償還日<input type="date" id="date" name="ganpon_dividend_date[]" value="<?= h($val['plannedDate']) ?>" required="required"/>
							元本償還率<input type="number" min="0" max="100.0" step="0.1" class="ganpon_fee" name="ganpon_fee[]"
								style="width: 14%;" value="<?= h($val['commission']) ?>" required="required"/> ％
							<ul id="thanks1">
								<li><input type="button" class="ganpon_delete_button" value="削除" /></li>
							</ul>
						</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</dd>

		<dt>オプション<span>Option</span><input type="button" id="option_add_button" value="オプション追加" /></dt>
		<dd>
			<table id="option">
				<tbody>
					<tr style="display: none;"><td>
						<input type="hidden" name="option_delFlg[]" value="">
						<input type="hidden" name="option_trans_no[]" value="">
						<input type="date" id="option_from" name="option_from[]" value="" />～<input type="date" id="option_to" name="option_to[]" value="" /><br>
						配当日<input type="date" id="option_dividend_date" name="option_dividend_date[]" value="" />
						配当率<input type="number" min="0" max="99.9" step="0.1" class="option_fee" name="option_fee[]" style="width: 14%;" value="" /> ％
						<br>
						株価<input type="number" id="stock_price" min="0" max="10000" step="1" name="stock_price[]" style="width: 13%;" value="" /> 円
						<br>
						<span id="option_xxxxx" name="option_xxxxx[]" >【株価×株数】</span>円
						<br>
						<span id="option_xxxxx" name="option_xxxxx[]" >【株数(根拠不明)】</span>株
						<br>
						<label for="name">内容</label>
						<br>
						<textarea id="option_memo" name="option_memo[]" cols="56" rows="10" maxlength="1000" style="max-width: 90%; height: 2em; padding: 2px 2%;
						border: 1px solid #cccccc; border-radius: 3px; background: #fafafa; -webkit-appearance: none; font-size: 100%;
						font-family: inherit; margin-top: 7px; height: 5em"></textarea>
						<br>
						<ul id="thanks1">
							<li><input type="button" class="option_delete_button_new" value="削除" /></li>
						</ul>
					</td></tr>
					<?php foreach ($optionList as $val) { ?>
						<tr><td>
							<input type="hidden" name="option_delFlg[]" value="">
							<input type="hidden" name="option_trans_no[]" value="<?= h($val['transNo']) ?>" />
							<input type="date" id="option_from" name="option_from[]" value="<?= h($val['termFrom']) ?>" required="required" />
								～<input type="date" id="option_to" name="option_to[]" value="<?= h($val['termTo']) ?>" required="required" /><br>
							配当日<input type="date" id="option_dividend_date" name="option_dividend_date[]" value="<?= h($val['plannedDate']) ?>" required="required" />
							配当率<input type="number" min="0" max="99.9" step="0.1" class="option_fee" name="option_fee[]" style="width: 14%;" value="<?= h($val['commission']) ?>" required="required" /> ％
							<br>
							株価<input type="number" id="stock_price" min="0" max="10000" step="1" name="stock_price[]" style="width: 13%;" value="<?= h($val['price']) ?>" /> 円
							<br>
							<span id="option_xxxxx" name="option_xxxxx[]" >【株価×株数】</span>円
							<br>
							<span id="option_xxxxx" name="option_xxxxx[]" >【株数(根拠不明)】</span>株
							<br>
							<label for="name">内容</label>
							<br>
							<textarea id="option_memo" name="option_memo[]" cols="56" rows="10" maxlength="1000" style="max-width: 90%; height: 2em; padding: 2px 2%;
							border: 1px solid #cccccc; border-radius: 3px; background: #fafafa; -webkit-appearance: none; font-size: 100%;
							font-family: inherit; margin-top: 7px; height: 5em"><?= h($val['memo']) ?></textarea>
							<br>
							<ul id="thanks1">
								<li><input type="button" class="option_delete_button" value="削除" /></li>
							</ul>
						</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</dd>
	</dl>

	<p id="form_submit" class="center">
		<input type="submit" name="form_submit_button" id="form_submit_button" value="修正する" />
	</p>
	<p id="form_submit" class="right">
		<input type="button" id="form_cancel_button" value="戻る" onClick="location.href='23_project_list.php'">
	</p>
	<p id="form_submit" class="right" >
		<input type="button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>

	<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
</form>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
<script src="./js/common.js"></script>
<script type="text/javascript">
<!--
//-->
</script>
<script>
jQuery(function($) {

	//初期表示で"〇回目"の表示
	showCountHaito();
	showCountGanpon();

	/*
	* データ設定
	*/
	$("#createHaito").on("click", function() {

		// 配当削除(登録済み分)
		$(".haito_delete_button").each(function(index, elem) {
			//$(elem).click();
			$(elem).trigger('click', [false]);
		});
		// 配当削除(新規設定分)
		$(".haito_delete_button_new").each(function(index, elem) {
			if (index !== 0) {	// フォーマット以外
				//$(elem).click();
				$(elem).trigger('click', [false]);
			}
		});

		//元本返済削除(登録済み分)
		$(".ganpon_delete_button").each(function(index, elem) {
			//$(elem).click();
			$(elem).trigger('click', [false]);
		});
		//元本返済削除(新規設定分)
		$(".ganpon_delete_button_new").each(function(index, elem) {
			if (index !== 0) {	// フォーマット以外
				//$(elem).click();
				$(elem).trigger('click', [false]);
			}
		});

		// 配当月日
		var term = parseInt($("#dividend_month").val(), 10);
		var divDate = parseInt($("#dividend_date").val(), 10);
		//if (divDate === 0) {
		//	term++;
		//}

		//0,29,30,31日入力の場合は月末として解釈
		var isGetumatu = false;
		if (divDate === 0 || divDate == 29 || divDate == 30 || divDate == 31) {
			divDate = 0;
			isGetumatu = true;
		}

		// 配当日
		{
			//待機期間の作成　0の場合は処理業務に必要な期間として1ヵ月を加算
			var waitPeriod = parseInt($("#wait_period").val(), 10);
			if( waitPeriod === 0 ){ waitPeriod = 1; }

			//待機期間加算を加算した配当可能日
			var posDate = new Date($("#start_date").val());
			posDate.setMonth(posDate.getMonth() + waitPeriod);

			//待機期間経過後の最初の配当日
			var haitoDate = new Date($("#start_date").val());
			haitoDate.setMonth(haitoDate.getMonth() + waitPeriod);
			if ( isGetumatu ) {
				haitoDate = new Date(haitoDate.getFullYear(), haitoDate.getMonth() + 1, 0);
				//haitoDate.setMonth(haitoDate.getMonth() + 1);
				//haitoDate.setDate(0);
			}else{
				haitoDate.setDate(divDate);
			}

			//最初の配当日が配当可能日より前の場合は1か月後
			if ( haitoDate < posDate ) {
				haitoDate.setMonth(haitoDate.getMonth() + 1);
				if( isGetumatu ){
					haitoDate = new Date(haitoDate.getFullYear(), haitoDate.getMonth() + 1, 0);
					//haitoDate.setMonth(haitoDate.getMonth() + 1);
					//haitoDate.setDate(0);
				}
			}
			// 1回目の配当日算出
			//if (divDate === 0) {
			//	var getumatu = new Date(haitoDate.getFullYear(), haitoDate.getMonth() + 1, 0);
			//	divDate = getumatu.getDate();
			//}
			//if (haitoDate.getDate() > divDate) {
			//	haitoDate.setMonth(haitoDate.getMonth() + 1);
			//}
			//haitoDate.setDate(divDate);
		}

		// 終了日
		var endDate = new Date($("#end_date").val());

		var registedCount = $("input[name='haito_dividend_date[]']").length;

		//配当回数
		var dividend_count = parseInt($("#dividend_count").val() || 0, 10);

		var index = 0;
		while (haitoDate < endDate) {

			// 種別「再生」の場合、配当は6回で終了。
			if ($("input[name='investment_type']:checked").val() === '01' && index === 6) {	break; }

			//配当回数を超えたら終了
			if ( dividend_count !== 0 && dividend_count <= index ) { break;	}

			index++;
			var haitoIndex = parseInt(index-1, 10) + registedCount;

			// 入力欄作成
			$('#haito_add_button').trigger('click', [false]);
			//$('#haito_add_button').click();

			// 配当日設定
			var ele_haitoDate = $("input[name='haito_dividend_date[]']");
			ele_haitoDate.eq(haitoIndex).val(makeDateStr(haitoDate));

			// 配当率設定
			var ele_haitoFee = $("input[name='haito_fee[]']");
			ele_haitoFee.eq(haitoIndex).val($("#dividend_rate").val());

			// 次の配当日を設定
			//haitoDate = new Date(haitoDate.getFullYear(), haitoDate.getMonth() + term, parseInt($("#dividend_date").val(), 10));
			if( isGetumatu ){
				haitoDate = new Date(haitoDate.getFullYear(), haitoDate.getMonth() + term + 1, 0);
			}else{
				haitoDate = new Date(haitoDate.getFullYear(), haitoDate.getMonth() + term, divDate);
			}
		}
		//配当回数を配当日生成数で上書き
		$("#dividend_count").val(index);

		//"〇回目"の表示
		showCountHaito();

		//元本返済日の作成
		var repayment_count = parseInt($("#repayment_count").val() || 0, 10);
		var repayment_rate = Math.round( (100/repayment_count) * 10) / 10; // 切り捨て

		var registedCount = $("input[name='ganpon_dividend_date[]']").length;

		for(var index = 0 ; index < repayment_count; index++) {
			$('#ganpon_add_button').trigger('click', [repayment_rate,false]);

			// 配当日設定
			var ganponIndex = parseInt(index, 10) + registedCount;
			var ele_ganponDate = $("input[name='ganpon_dividend_date[]']");
			ele_ganponDate.eq(ganponIndex).val(makeDateStr(haitoDate));
			//ele_ganponDate.eq(index+1).val(makeDateStr(haitoDate));
			// 次の元本返済日を設定
			if( isGetumatu ){
				haitoDate = new Date(haitoDate.getFullYear(), haitoDate.getMonth() + term + 1, 0);
			}else{
				haitoDate = new Date(haitoDate.getFullYear(), haitoDate.getMonth() + term, divDate);
			}
		}
		//"〇回目"の表示
		showCountGanpon();

		return false;
	});

	function makeDateStr(date) {
		var year = date.getFullYear();
		var month = date.getMonth() + 1;
		var day = date.getDate();

		var toTwoDigits = function (num, digit) {
			num += ''
			if (num.length < digit) {
				num = '0' + num
			}
			return num
		}

		var yyyy = toTwoDigits(year, 4);
		var mm = toTwoDigits(month, 2);
		var dd = toTwoDigits(day, 2);
		return yyyy + "-" + mm + "-" + dd;
	}

	function getNumber(_str){
		var arr = _str.split('');
		var out = new Array();
		for(var cnt=0; cnt<arr.length; cnt++){

			if (isNaN(arr[cnt]) == false){
				out.push(arr[cnt]);
			}
		}
		return Number(out.join(''));
	}

	//配当日の〇回目作成
	function showCountHaito(){
		var targetObj = $('#haito tbody .incNum:visible');
		var length = targetObj.length;
		for( var i=0; i<length; i++) {
			targetObj.eq(i).text(String(i+1));
		}
	}
	//元本返済日の〇回目作成
	function showCountGanpon(){
		var targetObj = $('#ganpon tbody .incNum:visible');
		var length = targetObj.length;
		for( var i=0; i<length; i++) {
			targetObj.eq(i).text(String(i+1));
		}
	}

	// 配当追加・削除
	//$("#haito_add_button").on("click", function() {
	$("#haito_add_button").on("click", function(event,isClick=true) {
		// 非表示項目の複製・表示
		$("#haito tbody tr:first-child").clone(true).appendTo("#haito tbody");
		$("#haito tbody tr:last-child").css("display", "table-row");
		$("#haito tbody tr:last-child").closest("tr").find('input[name="haito_dividend_date[]"]').prop('required', true);
		$("#haito tbody tr:last-child").closest("tr").find('input[name="haito_fee[]"]').prop('required', true);

		//手動で追加ボタンが押された場合の"〇回目"の表示
		if(isClick){ showCountHaito(); }

		// 行削除
		//$(".haito_delete_button_new").on("click", function() {
		$(".haito_delete_button_new").on("click", function(event,isClick=true) {
			$(this).closest("tr").remove();
			//手動で削除ボタンが押された場合の"〇回目"の表示
			if(isClick){ showCountHaito(); }
		});
	});

	//元本追加・削除
	//$("#ganpon_add_button").on("click", function() {
	$("#ganpon_add_button").on("click", function(event,repayment_rate,isClick=true) {
		// 非表示項目の複製・表示
		$("#ganpon tbody tr:first-child").clone(true).appendTo("#ganpon tbody");
		$("#ganpon tbody tr:last-child").css("display", "table-row");
		$("#ganpon tbody tr:last-child").closest("tr").find('input[name="ganpon_dividend_date[]"]').prop('required', true);
		$("#ganpon tbody tr:last-child").closest("tr").find('input[name="ganpon_fee[]"]').prop('required', true);
		//元本返済率の値を表示
		$("#ganpon tbody tr:last-child").closest("tr").find(".ganpon_fee").val(repayment_rate);

		//手動で追加ボタンが押された場合の"〇回目"の表示
		if(isClick){ showCountGanpon(); }

		// 行削除
		$(".ganpon_delete_button_new").on("click", function(event,isClick=true) {
			$(this).closest("tr").remove();
			//手動で削除ボタンが押された場合の"〇回目"の表示
			if(isClick){ showCountGanpon(); }
		});
	});

	// オプション追加
	$("#option_add_button").on("click", function() {
		// 非表示項目の複製・表示
		$("#option tbody tr:first-child").clone(true).appendTo("#option tbody");
		$("#option tbody tr:last-child").css("display", "table-row");
		$("#option tbody tr:last-child").closest("tr").find('input[name="option_from[]"]').prop('required', true);
		$("#option tbody tr:last-child").closest("tr").find('input[name="option_to[]"]').prop('required', true);
		$("#option tbody tr:last-child").closest("tr").find('input[name="option_dividend_date[]"]').prop('required', true);
		$("#option tbody tr:last-child").closest("tr").find('input[name="option_fee[]"]').prop('required', true);

		// 行削除
		$(".option_delete_button_new").on("click", function() {
			$(this).closest("tr").remove();
		});
	});

	// 削除ボタン
	// hidden項目「delFlg」を1にする。
	//$(".haito_delete_button").on("click", function() {
	$(".haito_delete_button").on("click", function(event,isClick=true) {
		$(this).closest("tr").find('input[name="haito_delFlg[]"]').val('1');
		$(this).closest("tr").find('input[name="haito_dividend_date[]"]').removeAttr('required');
		$(this).closest("tr").find('input[name="haito_fee[]"]').removeAttr('required');
		$(this).closest("tr").hide();
		//手動で削除ボタンが押された場合の"〇回目"の表示
		if(isClick){ showCountHaito(); }
	});

	// 削除ボタン
	// hidden項目「delFlg」を1にする。
	$(".ganpon_delete_button").on("click", function(event,isClick=true) {
		$(this).closest("tr").find('input[name="ganpon_delFlg[]"]').val('1');
		$(this).closest("tr").find('input[name="ganpon_dividend_date[]"]').removeAttr('required');
		$(this).closest("tr").find('input[name="ganpon_fee[]"]').removeAttr('required');
		$(this).closest("tr").hide();
		//手動で削除ボタンが押された場合の"〇回目"の表示
		if(isClick){ showCountGanpon(); }
	});

	// 削除ボタン
	// hidden項目「delFlg」を1にする。
	$(".option_delete_button").on("click", function() {
		$(this).closest("tr").find('input[name="option_delFlg[]"]').val('1');
		$(this).closest("tr").find('input[name="option_from[]"]').removeAttr('required');
		$(this).closest("tr").find('input[name="option_to[]"]').removeAttr('required');
		$(this).closest("tr").find('input[name="option_dividend_date[]"]').removeAttr('required');
		$(this).closest("tr").find('input[name="option_fee[]"]').removeAttr('required');
		$(this).closest("tr").hide();
	});

	//日付Validation
	$("#form_submit_button").on("click", function(){
		//基本情報日付
		$("#start_date").css("background-color", "");
		$("#end_date").css("background-color", "");
		var startDate = new Date($("#start_date").val());
		var endDate = new Date($("#end_date").val());
		if ( startDate >= endDate ) {
			$("#start_date").css("background-color", "#FFC0CB");
			$("#end_date").css("background-color", "#FFC0CB");
			alert("「案件終了日」が「覚書発行日」より前の日付になっています。");
			return false;
		}
		//オプション日付確認
		var targetFrom = $("input[name='option_from[]']:visible");
		var targetTo = $("input[name='option_to[]']:visible");
		var errorMessage = "";
		for( var i=0; i<targetFrom.length; i++ ) {
			targetFrom.eq(i).css("background-color", "");
			targetTo.eq(i).css("background-color", "");
			var fromDate = new Date(targetFrom.eq(i).val());
			var toDate = new Date(targetTo.eq(i).val());
			if ( fromDate > toDate ) {
				targetFrom.eq(i).css("background-color", "#FFC0CB");
				targetTo.eq(i).css("background-color", "#FFC0CB");
				errorMessage = "オプション：期間の入力内容を確認してください。";
			}
		}
		if( errorMessage ){
			alert(errorMessage);
			return false;
		}
	});

});
</script>


</body>
</html>
