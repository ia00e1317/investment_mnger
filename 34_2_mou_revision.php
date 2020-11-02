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

		// トランザクション開始
		$pdo->beginTransaction();

		// プロジェクト属性更新
		$info = new project_attr();
		$info->setAttrNo($_SESSION['proj_attr_no']);
		$info->setProjectNo($_POST['project_no']);
		$info->setInvestorNo($_POST['investor_no']);
		$info->setInvestmentAmount($_POST['hid_investment']);
		$info->setStartDate($_POST['start_date']);
		$info->setEndDate($_POST['end_date']);
		$info->setOldFlg(1);

		updateProjectAttr($pdo, $info);

		// プロジェクト属性
		$info = new project_attr();
		$info->setProjectNo($_POST['project_no']);
		$info->setInvestorNo($_POST['investor_no']);
		$info->setInvestmentAmount($_POST['hid_investment']);
		$info->setStartDate($_POST['start_date']);
		$info->setEndDate($_POST['end_date']);

		// プロジェクト属性登録
		$last_id = insertProjectAttr($pdo, $info);

		// 配当
		for($i = 1 ; $i < count($_POST['haito_dividend_date']); $i++) {

			$haito = new payment();
			$haito->setAttrNo($last_id);
			$haito->setType('01');
			$haito->setPlannedDate($_POST['haito_dividend_date'][$i]);
			$haito->setCommission($_POST['haito_fee'][$i]);
			$haito->setPlannedAmount(calcWithoutTax((int)$_POST['hid_investment'], (float)$_POST['haito_fee'][$i]));

			insertPayment($pdo, $haito);
		}

		// オプション
		for($i = 1 ; $i < count($_POST['option_dividend_date']); $i++) {

			$option = new payment();
			$option->setAttrNo($last_id);
			$option->setType('02');
			$option->setTermFrom($_POST['option_from'][$i]);
			$option->setTermTo($_POST['option_to'][$i]);
			$option->setPlannedDate($_POST['option_dividend_date'][$i]);
			$option->setCommission($_POST['option_fee'][$i]);
			$option->setPlannedAmount(calcWithoutTax((int)$_POST['hid_investment'], (float)$_POST['option_fee'][$i]));

			insertPayment($pdo, $option);
		}

		// 元本
		$principal = new payment();
		$principal->setAttrNo($last_id);
		$principal->setType('03');
		$principal->setPlannedDate($_POST['principal']);
		$principal->setCommission('100');
		$principal->setPlannedAmount($_POST['hid_investment']);
		insertPayment($pdo, $principal);

		// コミット
		$pdo->commit();

		// セッションクリア
		unset($_SESSION['proj_attr_no']);

		// 「覚書管理メニュー」画面へ移動
		header('Location: 33_mou_list.php');
		exit();

	// 初期表示
	} else {

		// 画面情報取得
		$projAtt = getProjectAttr($pdo, $_POST['proj_attr_no']);			// プロジェクト属性
		$haitoList = getPaymentList($pdo, $_POST['proj_attr_no'], '01');	// 配当
		$optionList = getPaymentList($pdo, $_POST['proj_attr_no'], '02');	// オプション
		$principal = getPaymentList($pdo, $_POST['proj_attr_no'], '03')[0];	// 元本

		$projectList = getProjectList($pdo);								// 投資案件取得
		$investorList = getInvestorList($pdo, array());						// 投資家取得
		$paymentList = getActualPaymentList($pdo, $_POST['proj_attr_no']);	// 配当 (完了)
		$_SESSION['proj_attr_no'] = $_POST['proj_attr_no'];
	}

	// CSRF対策用トークン取得
	$csrf_token = setCSRFtoken();

} catch (PDOException $e) {
	echo "<script type=\"text/javascript\">alert(\'データベース接続・操作処理エラー\');</script>";
} finally {
	$stmt = null;

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
<title>覚書改定</title>
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
	<h1>覚書改定</h1>
	<dl>
		<dt>投資家名<span>Investor Name</span></dt>
		<dd>
			<select id="investor_no" name="investor_no" required="required">
				<?php foreach ($investorList as $key => $val) { ?>
					<option value="<?= h($key) ?>" <?php if (strcmp($projAtt->getInvestorNo(), $key) == 0) { echo 'selected'; } ?>>
						<?= h($val) ?>
					</option>
				<?php } ?>
			</select>
		</dd>

		<dt>投資案件名<span>project name</span></dt>
		<dd>
			<select id="project_no" name="project_no" required="required">
				<?php foreach ($projectList as $key => $val) { ?>
					<option value="<?= h($key) ?>" <?php if (strcmp($projAtt->getProjectNo(), $key) == 0) { echo 'selected'; } ?>>
						<?= h($val) ?>
					</option>
				<?php } ?>
			</select>
		</dd>

		<dt>投資額<span>investment</span></dt>
		<dd>
			<input type="text" id="investment" name="investment"
				value="<?= h(number_format(intval($projAtt->getInvestmentAmount()))) ?>" required="required"/> 円
			<input type="hidden" id="hid_investment" name="hid_investment" value="<?= h($projAtt->getInvestmentAmount()) ?>" >
		</dd>

		<dt>覚書発行日<span>start date</span></dt>
		<dd>
			<input type="date" id="start_date" name="start_date" value="<?= h($projAtt->getStartDate()) ?>" required="required"/>
		</dd>

		<dt>案件終了日<span>end date</span></dt>
		<dd>
			<input type="date" id="end_date" name="end_date" value="<?= h($projAtt->getEndDate()) ?>" required="required"/>
		</dd>

		<dt>配当 (完了)<span>dividend (complete)</span></dt>
		<dd>
		<?php if ($paymentList) { ?>
			<?php $i = 0 ?>
			<?php foreach ($paymentList as $value) { ?>
				<?php
					if ($i > 0) {echo '<br>';}
					$i++;
				?>
				<ul id="thanks2">
					<li> 支払予定日：</li>
					<li><?= date('Y年n月j日', strtotime($value['plannedDate'])) ?></li>
				</ul>
				<ul id="thanks2">
					<li> 支払実績日：</li>
					<li><?= date('Y年n月j日', strtotime($value['actualDate'])) ?></li>
				</ul>
				<ul id="thanks2">
					<li> 支払予定額：</li>
					<li><?= number_format($value['plannedAmount']) ?> 円</li>
				</ul>
				<ul id="thanks2">
					<li> 支払実績額：</li>
					<li><?= number_format($value['actualAmount']); ?> 円</li>
				</ul>
				<input type="hidden" name="tranId" value="<?= $value['transNo'] ?>">
				<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
			<?php } ?>
		<?php } else {echo 'なし';} ?>
		</dd>

		<dt>配当<span>dividend</span><input type="button" id="haito_add_button" value="配当日追加" /></dt>
		<dd class="required">
			<table id="haito">
				<tbody>
				    <tr style="display: none;"><td>
						<input type="hidden" name="haito_delFlg[]" value="">
						<input type="hidden" name="haito_trans_no[]" value="">
						配当日<input type="date" id="date" name="haito_dividend_date[]" value=""/>
						手数料<input type="number" min="0" max="99.9" step="0.1" class="haito_fee" name="haito_fee[]" style="width: 13%;" value=""/> ％
						<ul id="thanks1">
							<li><span id="haito_amount"></span> 円</li>
							<li><input type="button" class="haito_delete_button_new" value="削除" /></li>
						</ul>
					</td></tr>
				</tbody>
			</table>
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
							<ul id="thanks1">
								<li><span id="option_amount">0</span> 円</li>
								<li><input type="button" class="option_delete_button_new" value="削除" /></li>
							</ul>
						</td>
					</tr>
				</tbody>
			</table>
		</dd>

		<dt>元本<span>principal</span></dt>
		<dd>返却日
			<input type="hidden" name="principal_trans_no" value="<?= h($principal['transNo']) ?>">
			<input type="date" id="principal" name="principal" value="<?= h($principal['plannedDate']) ?>" required="required"/>
		</dd>
	</dl>

	<p id="form_submit" class="center">
		<input type="submit" name="form_submit_button" value="改定する" />
	</p>
	<p id="form_submit" class="right">
		<input type="button" id="form_cancel_button" value="戻る" onClick="location.href='33_mou_list.php'">
	</p>
	<p id="form_submit" class="right" >
		<input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" />
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
	$("#principal").val(data['endDate']);
}

/*
 * データ設定(配当)
 */
function setDataHaito(data) {

	var registedCount = $("input[name='haito_dividend_date[]']").length;

	$.each(data,function(index,obj){

		var haitoIndex = parseInt(index, 10) + registedCount;

		// 入力欄作成
		$('#haito_add_button').click();

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

	// 配当追加・削除
	$("#haito_add_button").on("click", function() {

		// 非表示項目の複製・表示
		$("#haito tbody tr:first-child").clone(true).appendTo("#haito tbody");
		$("#haito tbody tr:last-child").css("display", "table-row");
		$("#haito tbody tr:last-child").closest("tr").find('input[name="haito_dividend_date[]"]').prop('required', true);
		$("#haito tbody tr:last-child").closest("tr").find('input[name="haito_fee[]"]').prop('required', true);

		// 行削除
		$(".haito_delete_button_new").on("click", function() {
			$(this).closest("tr").remove();
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

	// 自動計算
	$(".option_fee").blur(function() {
		var fee = $(this).val();
		var investment = $("#hid_investment").val();
		$(this).closest("tr").find('#option_amount').html(calcWithoutTax(investment, fee));
	});

	// 削除ボタン
	// hidden項目「delFlg」を1にする。
	$(".haito_delete_button").on("click", function() {
		$(this).closest("tr").find('input[name="haito_delFlg[]"]').val('1');
		$(this).closest("tr").find('input[name="haito_dividend_date[]"]').removeAttr('required');
		$(this).closest("tr").find('input[name="haito_fee[]"]').removeAttr('required');
		$(this).closest("tr").hide();
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

	$('#investment').on('keyup blur',function(){
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

		// オプション配当金設定
		$("input[name='option_fee[]']").each(function(index, elem) {
			if (index !== 0) {
				$(elem).blur();
			}
		});
	});

	$('#project_no').on('change',function(){

		// 配当削除(登録済み分)
		$(".haito_delete_button").each(function(index, elem) {
			$(elem).click();
		});
		// 配当削除(新規設定分)
		$(".haito_delete_button_new").each(function(index, elem) {
			if (index !== 0) {	// フォーマット以外
				$(elem).click();
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
});
</script>

</body>
</html>
