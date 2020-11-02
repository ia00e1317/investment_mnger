<?php
session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (! isset($_SESSION['loginUserID'])) {
    header('Location: 02_logout.php');
    exit();
}

require_once 'DSN.php';
require_once 'investor.php';
require_once 'project.php';
require_once 'project_attr.php';
require_once 'payment.php';
require_once 'security.php';
require_once 'util.php';

ob_start();

try {

	// DB接続
	$pdo = db_connect();

	// 送信ボタン「form_submit_button」が押された際の処理
	if (isset($_POST['form_submit_button'])) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// プロジェクト属性更新
		$info = new project_attr();
		$info->setAttrNo($_SESSION['proj_attr_no']);
		$info->setProjectNo($_POST['project_no']);
		$info->setInvestorNo($_POST['investor_no']);
		$info->setInvestmentAmount($_POST['hid_investment']);
		$info->setStartDate($_POST['start_date']);
		$info->setEndDate($_POST['end_date']);

		// 相関チェック
		$error = $info->validation();

		// エラー無し
		if (count($error) < 1) {

			// トランザクション開始
			$pdo->beginTransaction();

			// ロック取得
			$sql_sel = 'SELECT PROJ_ATTR_NO, INVESTMENT_AMOUNT FROM PROJECT_ATTRIBUTE WHERE PROJ_ATTR_NO = :proj_attr_no FOR UPDATE';
			$stmt = $pdo->prepare($sql_sel);
			$stmt->bindParam(':proj_attr_no', $_SESSION['proj_attr_no'], PDO::PARAM_STR);
			$stmt->execute();

			//覚書コード作成
			$info->setAttrCode(makeAttrCode($pdo, $info));

			// プロジェクト属性更新
			updateProjectAttr($pdo, $info);

			// 配当更新
			for ($i = 1 ; $i < count($_POST['haito_trans_no']); $i++) {

				$haito = new payment();
				$haito->setAttrNo($_SESSION['proj_attr_no']);
				$haito->setTransNo($_POST['haito_trans_no'][$i]);
				$haito->setType('01');
				$haito->setPlannedDate($_POST['haito_dividend_date'][$i]);
				$haito->setCommission($_POST['haito_fee'][$i]);
				$haito->setPlannedAmount(calcWithoutTax((int)$_POST['hid_investment'], (float)$_POST['haito_fee'][$i]));

				if (strcmp($_POST['haito_delFlg'][$i], '1') == 0) {
					// DELETE
					deletePayment($pdo, $haito->getTransNo());
				} else if (strcmp($_POST['haito_trans_no'][$i], '') == 0) {
					// INSERT
					insertPayment($pdo, $haito);
				} else {
					// UPDATE
					updatePayment($pdo, $haito);
				}
			}

			//元本返済更新
			for ($i = 1 ; $i < count($_POST['ganpon_trans_no']); $i++) {

				$ganpon = new payment();
				$ganpon->setAttrNo($_SESSION['proj_attr_no']);
				$ganpon->setTransNo($_POST['ganpon_trans_no'][$i]);
				$ganpon->setType('03');
				$ganpon->setPlannedDate($_POST['ganpon_dividend_date'][$i]);
				$ganpon->setCommission($_POST['ganpon_fee'][$i]);
				$ganpon->setPlannedAmount(floor( $_POST['hid_investment'] * (float)$_POST['ganpon_fee'][$i] / 100 ));

				if (strcmp($_POST['ganpon_delFlg'][$i], '1') == 0) {
					// DELETE
					deletePayment($pdo, $ganpon->getTransNo());
				} else if (strcmp($_POST['ganpon_trans_no'][$i], '') == 0) {
					// INSERT
					insertPayment($pdo, $ganpon);
				} else {
					// UPDATE
					updatePayment($pdo, $ganpon);
				}
			}

			// オプション更新
			for($i = 1 ; $i < count($_POST['option_dividend_date']); $i++) {

				$option = new payment();
				$option->setAttrNo($_SESSION['proj_attr_no']);
				$option->setTransNo($_POST['option_trans_no'][$i]);
				$option->setType('02');
				$option->setTermFrom($_POST['option_from'][$i]);
				$option->setTermTo($_POST['option_to'][$i]);
				$option->setPlannedDate($_POST['option_dividend_date'][$i]);
				$option->setCommission($_POST['option_fee'][$i]);
				$option->setStockPrice($_POST['stock_price'][$i]);
				$option->setOptionMemo($_POST['option_memo'][$i]);
				$option->setPlannedAmount(calcWithoutTax((int)$_POST['hid_investment'], (float)$_POST['option_fee'][$i]));

				if (strcmp($_POST['option_delFlg'][$i], '1') == 0) {
					// DELETE
					deletePayment($pdo, $option->getTransNo());
				} else if (strcmp($_POST['option_trans_no'][$i], '') == 0) {
					// INSERT
					insertPayment($pdo, $option);
				} else {
					// UPDATE
					updatePayment($pdo, $option);
				}
			}

			$pdo->commit();

			// セッションクリア
			unset($_SESSION['proj_attr_no']);

			// 「覚書管理メニュー」画面へ移動
			header('Location: 33_mou_list.php');
			exit();
		}

		//Validationエラー時の復帰
		$haitoList = getPaymentList($pdo, $_SESSION['proj_attr_no'], '01');	// 配当
		$ganponList = getPaymentList($pdo, $_SESSION['proj_attr_no'], '03');	// 元本
		$optionList = getPaymentList($pdo, $_SESSION['proj_attr_no'], '02');	// オプション

	// 初期表示
	} else {

		// 画面情報取得
		$info = getProjectAttr($pdo, $_POST['proj_attr_no']);			// プロジェクト属性
		$haitoList = getPaymentList($pdo, $_POST['proj_attr_no'], '01');	// 配当
		$ganponList = getPaymentList($pdo, $_POST['proj_attr_no'], '03');	// 元本
		$optionList = getPaymentList($pdo, $_POST['proj_attr_no'], '02');	// オプション
		//$principal = getPaymentList($pdo, $_POST['proj_attr_no'], '03')[0];	// 元本

		$_SESSION['proj_attr_no'] = $_POST['proj_attr_no'];
	}

	$projectList = getProjectList($pdo);								// 投資案件取得
	$investorList = getInvestorList($pdo, array());						// 投資家取得

	// CSRF対策用トークン取得
	$csrf_token = setCSRFtoken();

} catch (PDOException $e) {
	echo "<script type=\"text/javascript\">alert(\'データベース接続・操作処理エラー\');</script>";
} finally {
	$stmt = null;
	$pdo = null;
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<title>覚書情報修正</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" href="css/mailform.css" />
<link rel="stylesheet" href="css/thanks.css" />

</head>
<body onload="">
<div id="main">
<form action="" method="post" id="mail_form">
	<h1>覚書情報修正</h1>
	<dl>
		<dt>投資家名<span>Investor Name</span></dt>
		<dd>
			<select id="investor_no" name="investor_no" required="required">
				<option value="">選択してください</option>
				<?php foreach ($investorList as $key => $val) { ?>
					<option value="<?= h($key) ?>" <?php if (strcmp($info->getInvestorNo(), $key) == 0) { echo 'selected'; } ?>>
						<?= h($val) ?>
					</option>
				<?php } ?>
			</select>
		</dd>

		<dt>投資案件名<span>project name</span></dt>
		<dd>
			<select id="project_no" name="project_no" required="required">
				<?php foreach ($projectList as $key => $val) { ?>
					<option value="<?= h($key) ?>" <?php if (strcmp($info->getProjectNo(), $key) == 0) { echo 'selected'; } ?>>
						<?= h($val) ?>
					</option>
				<?php } ?>
			</select>
		</dd>

		<dt>投資額<span>investment</span></dt>
		<dd>
			<input type="text" id="investment" name="investment"
				value="<?= h(number_format(intval($info->getInvestmentAmount()))) ?>" required="required" maxlength="16"/> 円
			<input type="hidden" id="hid_investment" name="hid_investment" value="<?= h($info->getInvestmentAmount()) ?>" >
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

		<dt>配当<span>dividend</span><input type="button" id="haito_add_button" value="配当日追加" /></dt>
		<dd class="required">
			<table id="haito">
				<tbody>
				    <tr style="display: none;"><td>
						<input type="hidden" name="haito_delFlg[]" value="">
						<input type="hidden" name="haito_trans_no[]" value="">
  						<span class="incNum"></span>回目
						配当日<input type="date" id="date" name="haito_dividend_date[]" value=""/>
						配当率<input type="number" min="0" max="99.9" step="0.1" class="haito_fee" name="haito_fee[]" style="width: 13%;" value=""/> ％
						<ul id="thanks1">
							<li><span id="haito_amount"></span> 円</li>
							<li><input type="button" class="haito_delete_button_new" value="削除" /></li>
						</ul>
					</td></tr>
					<?php foreach ($haitoList as $val) { ?>
						<tr><td>
							<?php if (is_null($val['actualDate'])) {?>
								<input type="hidden" name="haito_delFlg[]" value="">
								<input type="hidden" name="haito_trans_no[]" value="<?= h($val['transNo']) ?>">
								<span class="incNum"></span>回目
								配当日<input type="date" id="date" name="haito_dividend_date[]" value="<?= h($val['plannedDate']) ?>" required="required"/>
								配当率<input type="number" min="0" max="99.9" step="0.1" class="haito_fee" name="haito_fee[]"
									style="width: 13%;" value="<?= h($val['commission']) ?>" required="required"/> ％
								<ul id="thanks1">
									<li><span id="haito_amount"><?= h(number_format($val['plannedAmount'])) ?></span> 円</li>
									<li><input type="button" class="haito_delete_button" value="削除" /></li>
								</ul>
							<?php } else {  ?>
								<span class="incNum"></span>回目
								配当日<input type="date" value="<?= h($val['plannedDate']) ?>" disabled="disabled"/>
								配当率<input type="number" style="width: 13%;" value="<?= h($val['commission']) ?>" disabled="disabled"/> ％
								<ul id="thanks1">
									<li><span id="haito_amount"><?= h(number_format($val['plannedAmount'])) ?></span> 円</li>
									<!-- <li><input type="button" class="haito_delete_button" value="削除" disabled="disabled"/></li> -->
								</ul>
							<?php } ?>
						</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</dd>

		<dt>元本償還<span>principal repayment</span><input type="button" id="ganpon_add_button" value="元本償還追加"/></dt>
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
							<li><span class="ganpon_amount">0</span> 円</li>
							<li><input type="button" class="ganpon_delete_button_new" value="削除" /></li>
						</ul>
					</td></tr>

					<?php foreach ($ganponList as $val) { ?>
						<tr><td>
							<?php if (is_null($val['actualDate'])) {?>
								<input type="hidden" name="ganpon_delFlg[]" value="">
								<input type="hidden" name="ganpon_trans_no[]" value="<?= h($val['transNo']) ?>">
								<span class="incNum"></span>回目
								償還日<input type="date" id="date" name="ganpon_dividend_date[]" value="<?= h($val['plannedDate']) ?>" required="required"/>
								元本償還率<input type="number" min="0" max="100.0" step="0.1" class="ganpon_fee" name="ganpon_fee[]"
									style="width: 13%;" value="<?= h($val['commission']) ?>" required="required"/> ％
								<ul id="thanks1">
									<li><span class="ganpon_amount"><?= h(number_format($val['plannedAmount'])) ?></span> 円</li>
									<li><input type="button" class="ganpon_delete_button" value="削除" /></li>
								</ul>
							<?php } else {  ?>
								<span class="incNum"></span>回目
								償還日<input type="date" value="<?= h($val['plannedDate']) ?>" disabled="disabled"/>
								元本償還率<input type="number" style="width: 13%;" value="<?= h($val['commission']) ?>" disabled="disabled"/> ％
								<ul id="thanks1">
									<li><span class="ganpon_amount"><?= h(number_format($val['plannedAmount'])) ?></span> 円</li>
									<!-- <li><input type="button" class="ganpon_delete_button" value="削除" disabled="disabled"/></li> -->
								</ul>
							<?php } ?>
						</td></tr>
					<?php } ?>
				</tbody>
			</table>
			<span id="ganpon_investment" name="ganpon_investment" style="display:none;">0</span><span style="display:none;">円</span><!--<?= h(number_format($info->getInvestmentAmount())) ?>-->
		</dd>

		<dt>オプション<span>Option</span><input type="button" id="option_add_button" value="オプション追加" /></dt>
		<dd>
			<table id="option">
				<tbody>
					<tr style="display: none;">
						<td>
							<input type="hidden" name="option_delFlg[]" value="">
							<input type="hidden" name="option_trans_no[]" value="">
							<input type="date" id="option_from" name="option_from[]" value="" />～<input type="date" id="option_to" name="option_to[]" value="" /><br>
							配当日<input type="date" id="option_dividend_date" name="option_dividend_date[]" value="" />
							配当率<input type="number" min="0" max="99.9" step="0.1" class="option_fee" name="option_fee[]" style="width: 13%;" value="" /> ％
							<br>
							株価<input type="number" id="stock_price" min="0" max="10000" step="1" name="stock_price[]" style="width: 13%;" value="" /> 円
							<br>
							<span id="option_xxxxx" name="option_xxxxx[]" >【株価×株数】</span> 円
							<br>
							<span id="option_xxxxx" name="option_xxxxx[]" >【株数(根拠不明)】</span>株
							<br>
							<label for="name">内容</label>
							<br>
							<textarea id="option_memo" name="option_memo[]" cols="56" rows="10" maxlength="1000" style="max-width: 90%; height: 2em; padding: 2px 2%;
							border: 1px solid #cccccc; border-radius: 3px; background: #fafafa; -webkit-appearance: none; font-size: 100%;
							font-family: inherit; margin-top: 7px; height: 5em"></textarea>
							<ul id="thanks1">
								<li><input type="button" class="option_delete_button_new" value="削除" /></li>
							</ul>
						</td>
					</tr>

					<?php foreach ($optionList as $val) { ?>
						<tr><td>
						<?php if (is_null($val['actualDate'])) {?>
							<input type="hidden" name="option_delFlg[]" value="">
							<input type="hidden" name="option_trans_no[]" value="<?= h($val['transNo']) ?>" />
							<input type="date" id="option_from" name="option_from[]" value="<?= h($val['termFrom']) ?>" required="required" />
								～<input type="date" id="option_to" name="option_to[]" value="<?= h($val['termTo']) ?>" required="required" /><br>
							配当日<input type="date" id="option_dividend_date" name="option_dividend_date[]" value="<?= h($val['plannedDate']) ?>" required="required" />
							配当率<input type="number" min="0" max="99.9" step="0.1" class="option_fee" name="option_fee[]" style="width: 13%;" value="<?= h($val['commission']) ?>" required="required" /> ％
							<br>
							株価<input type="number" id="stock_price" min="0" max="10000" step="1" name="stock_price[]" style="width: 13%;" value="<?= h($val['stockPrice']) ?>" /> 円
							<br>
							<span id="option_xxxxx" name="option_xxxxx[]" >【株価×株数】</span>円
							<br>
							<span id="option_xxxxx" name="option_xxxxx[]" >【株数(根拠不明)】</span>株
							<br>
							<label for="name">内容</label>
							<br>
							<textarea id="option_memo" name="option_memo[]" cols="56" rows="10" maxlength="1000" style="max-width: 90%; height: 2em; padding: 2px 2%;
							border: 1px solid #cccccc; border-radius: 3px; background: #fafafa; -webkit-appearance: none; font-size: 100%;
							font-family: inherit; margin-top: 7px; height: 5em"><?= h($val['optionMemo']) ?></textarea>
							<ul id="thanks1">
								<!--<li><span id="option_amount"><?= h(number_format($val['plannedAmount'])) ?></span> 円</li>-->
								<li><input type="button" class="option_delete_button" value="削除" /></li>
							</ul>
						<?php } else { ?>
							<input type="date" value="<?= h($val['termFrom']) ?>" disabled="disabled" />
								～<input type="date" value="<?= h($val['termTo']) ?>" disabled="disabled" /><br>
							配当日<input type="date" value="<?= h($val['plannedDate']) ?>" disabled="disabled" />
							配当率<input type="number" class="option_fee" style="width: 13%;" value="<?= h($val['commission']) ?>" disabled="disabled" /> ％
							<br>
							株価<input type="number" id="stock_price" min="0" max="10000" step="1" style="width: 13%;" value="<?= h($val['stockPrice']) ?>" />円
							<br>
							<span id="option_xxxxx" name="option_xxxxx[]" >【株価×株数】</span>円
							<br>
							<span id="option_xxxxx" name="option_xxxxx[]" >【株数(根拠不明)】</span>株
							<br>
							<label for="name">内容</label>
							<br>
							<textarea id="option_memo" cols="56" rows="10" maxlength="1000" style="max-width: 90%; height: 2em; padding: 2px 2%;
							border: 1px solid #cccccc; border-radius: 3px; background: #fafafa; -webkit-appearance: none; font-size: 100%;
							font-family: inherit; margin-top: 7px; height: 5em"><?= h($val['optionMemo']) ?></textarea>
							<ul id="thanks1">
								<!--<li><span id="option_amount"><?= h(number_format($val['plannedAmount'])) ?></span> 円</li>-->
								<!-- <li><input type="button" class="option_delete_button" value="削除" disabled="disabled"/></li> -->
							</ul>
						<?php } ?>
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
		<input type="button" id="form_cancel_button" value="戻る" onClick="location.href='33_mou_list.php'">
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

/*
 * データ設定
 */
function setDataBase(data) {
	$("#start_date").val(data['startDate']);
	$("#end_date").val(data['endDate']);
	//$("#principal").val(data['endDate']);
}

/*
 * データ設定(配当)
 */
function setDataHaito(data) {

	var registedCount = $("input[name='haito_dividend_date[]']").length;

	$.each(data,function(index,obj){

		var haitoIndex = parseInt(index, 10) + registedCount;

		// 入力欄作成
		//$('#haito_add_button').click();
		$('#haito_add_button').trigger('click', [false]);

		// 配当日設定
		var ele_haitoDate = $("input[name='haito_dividend_date[]']");
		ele_haitoDate.eq(haitoIndex).val(obj['plannedDate']);

		// 配当率設定
		var ele_haitoFee = $("input[name='haito_fee[]']");
		ele_haitoFee.eq(haitoIndex).val(obj['commission']);

		// 配当金設定
		ele_haitoFee.eq(haitoIndex).blur();
	})
}

/*
 * データ設定(元本返済)
 */
function setDataGanpon(data) {

	var registedCount = $("input[name='ganpon_dividend_date[]']").length;

	$.each(data,function(index,obj){

		var ganponIndex = parseInt(index, 10) + registedCount;

		// 入力欄作成
		//$('#ganpon_add_button').click();
		$('#ganpon_add_button').trigger('click', [false]);

		// 配当日設定
		var ele_ganponDate = $("input[name='ganpon_dividend_date[]']");
		ele_ganponDate.eq(ganponIndex).val(obj['plannedDate']);

		// 配当率設定
		var ele_ganponFee = $("input[name='ganpon_fee[]']");
		ele_ganponFee.eq(ganponIndex).val(obj['commission']);

		// 配当金設定
		ele_ganponFee.eq(ganponIndex).blur();
	})
}

/*
 * データ設定(オプション)
 */
function setDataOption(data) {

	var registedCount = $("input[name='option_dividend_date[]']").length;

	$.each(data,function(index,obj){

		var optionIndex = parseInt(index, 10) + registedCount;

		// 入力欄作成
		$('#option_add_button').click();

		// 期間(from)設定
		var ele_optionFrom = $("input[name='option_from[]']");
		ele_optionFrom.eq(optionIndex).val(obj['termFrom']);

		// 期間(to)設定
		var ele_optionTo = $("input[name='option_to[]']");
		ele_optionTo.eq(optionIndex).val(obj['termTo']);

		// 配当日設定
		var ele_optionDate = $("input[name='option_dividend_date[]']");
		ele_optionDate.eq(optionIndex).val(obj['plannedDate']);

		// 配当率設定
		var ele_optionFee = $("input[name='option_fee[]']");
		ele_optionFee.eq(optionIndex).val(obj['commission']);

		// 配当金設定
		ele_optionFee.eq(optionIndex).blur();
	})
}

//-->
</script>
<script>
jQuery(function($) {

	//"〇回目"の表示
	showCountHaito();
	showCountGanpon();

	//元本償還総額の表示
	showGanponTotal();

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

	// 元本返済追加・削除
	//$("#ganpon_add_button").on("click", function() {
	$("#ganpon_add_button").on("click", function(event,isClick=true) {
		// 非表示項目の複製・表示		
		$("#ganpon tbody tr:first-child").clone(true).appendTo("#ganpon tbody");
		$("#ganpon tbody tr:last-child").css("display", "table-row");
		$("#ganpon tbody tr:last-child").closest("tr").find('input[name="ganpon_dividend_date[]"]').prop('required', true);
		$("#ganpon tbody tr:last-child").closest("tr").find('input[name="ganpono_fee[]"]').prop('required', true);

		//手動で追加ボタンが押された場合
		if(isClick){
			//"〇回目"の表示
			showCountGanpon();
			//元本償還総額の表示
			showGanponTotal();
		}

		// 行削除
		//$("ganpon_delete_button_new").on("click", function() {
		$(".ganpon_delete_button_new").on("click", function(event,isClick=true) {
			$(this).closest("tr").remove();
			//手動で削除ボタンが押された場合
			if(isClick){
				//"〇回目"の表示
				showCountGanpon();
				//元本償還総額の表示
				showGanponTotal();
			}
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

	// 自動計算
	$(".haito_fee").blur(function() {
		var investment = $("#hid_investment").val();
		var fee = $(this).val();
		$(this).closest("tr").find('#haito_amount').html(calcWithoutTax(investment, fee));
	});

	// 自動計算(元本返済)
	$(".ganpon_fee").blur(function() {
		var investment = $("#hid_investment").val();
		var fee = $(this).val();

		//小数を切り捨てて整数で表示
		var val = Math.floor(investment * fee / 100);
		//小数点3位を四捨五入して2位まで表示
		//var val = Math.round((investment * fee / 100) * 100) / 100;

		$(this).closest("tr").find('.ganpon_amount').html(val.toLocaleString(undefined, { maximumFractionDigits: 20 }));

		//元本償還総額の表示
		showGanponTotal();
	});

	// 自動計算
	$(".option_fee").blur(function() {
		var fee = $(this).val();
		var investment = $("#hid_investment").val();
		$(this).closest("tr").find('#option_amount').html(calcWithoutTax(investment, fee));
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
	//$(".ganpon_delete_button").on("click", function() {
	$(".ganpon_delete_button").on("click", function(event,isClick=true) {
		$(this).closest("tr").find('input[name="ganpon_delFlg[]"]').val('1');
		$(this).closest("tr").find('input[name="ganpon_dividend_date[]"]').removeAttr('required');
		$(this).closest("tr").find('input[name="ganpon_fee[]"]').removeAttr('required');
		$(this).closest("tr").hide();
		//手動で削除ボタンが押された場合
		if(isClick){
			//"〇回目"の表示
			showCountGanpon();
			//元本償還総額の表示
			showGanponTotal();	
		}
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

	//$('#investment').on('keyup blur',function(){
	$('#investment').on('blur',function(){
		updateTextView($(this), $('#hid_investment'));
	});

	$('#investment').on('blur',function(){

		var pNo = $('#project_name').val();
		if (pNo === '') {
			return false;
		}

		// 配当金設定
		$("input[name='haito_fee[]']").each(function(index, elem) {
			if (index !== 0) {
				$(elem).blur();
			}
		});

		// 元本返済金設定
		$("input[name='ganpon_fee[]']").each(function(index, elem) {
			if (index !== 0) {
				$(elem).blur();
			}
		});

		// オプション配当金設定
		$("input[name='option_fee[]']").each(function(index, elem) {
			if (index !== 0) {
				$(elem).blur();
			}
		});

		//元本償還総額の表示
		showGanponTotal();

	});

	$('#project_no').on('change',function(){

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

		// 元本返済削除(登録済み分)
		$(".ganpon_delete_button").each(function(index, elem) {
			//$(elem).click();
			$(elem).trigger('click', [false]);
		});
		// 元本返済削除(新規設定分)
		$(".ganpon_delete_button_new").each(function(index, elem) {
			if (index !== 0) {	// フォーマット以外
				//$(elem).click();
				$(elem).trigger('click', [false]);
			}
		});

		// オプション削除(登録済み分)
		$(".option_delete_button").each(function(index, elem) {
			$(elem).click();
		});
		// オプション削除(新規設定分)
		$(".option_delete_button_new").each(function(index, elem) {
			if (index !== 0) {	// フォーマット以外
				$(elem).click();
			}
		});

		// プロジェクトNo取得
		var pNo = $('#project_no').val();
		if (pNo === '') {
			return false;
		}

		//Ajax通信
		$.ajax({
			url: './getProjectInfo.php',
			type: 'post',
//			contentType: 'Content-Type: application/json; charset=UTF-8',
			data:{'pNo' : pNo},
			dataType : 'text',
		}).done(function(data){
			setDataBase($.parseJSON(data));
		}).fail(function(XMLHttpRequest, textStatus, error){
			alert('検索に失敗しました。');
		});

		//Ajax通信(配当定義情報)
		$.ajax({
			url: './getPaymentDef.php',
			type: 'post',
//			contentType: 'Content-Type: application/json; charset=UTF-8',
			data:{
				'pNo' : pNo,
				'type' : '01'},
			dataType : 'text',
		}).done(function(data){
			setDataHaito($.parseJSON(data));
			showCountHaito();
		}).fail(function(XMLHttpRequest, textStatus, error){
			alert('検索に失敗しました。');
		});

		//Ajax通信(元本返済定義情報)
		$.ajax({
			url: './getPaymentDef.php',
			type: 'post',
//			contentType: 'Content-Type: application/json; charset=UTF-8',
			data:{
				'pNo' : pNo,
				'type' : '03'},
			dataType : 'text',
		}).done(function(data){
			setDataGanpon($.parseJSON(data));
			showCountGanpon();
			showGanponTotal();
		}).fail(function(XMLHttpRequest, textStatus, error){
			alert('検索に失敗しました。');
		});

		//Ajax通信(オプション定義情報)
		$.ajax({
			url: './getPaymentDef.php',
			type: 'post',
//			contentType: 'Content-Type: application/json; charset=UTF-8',
			data:{
				'pNo' : pNo,
				'type' : '02'},
			dataType : 'text',
		}).done(function(data){
			setDataOption($.parseJSON(data));
		}).fail(function(XMLHttpRequest, textStatus, error){
			alert('検索に失敗しました。');
		});
	});

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

	//元本償還総額表示
	function showGanponTotal(){
		var targetObj = $('.ganpon_amount:visible');
		var length = targetObj.length;
		var total = 0.00;
		for( var i=0; i<length; i++) {
			val = parseFloat(targetObj.eq(i).html().replace(/,/g, ''));
			total += val;
		}
		$('#ganpon_investment').html(Math.round(total).toLocaleString(undefined, { maximumFractionDigits: 20 }));
	}

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
