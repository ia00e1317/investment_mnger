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

		// プロジェクト属性
		$info = new project_attr();
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

			//覚書コード作成
			$info->setAttrCode(makeAttrCode($pdo, $info));

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

			// 元本返済
			for($i = 1 ; $i < count($_POST['ganpon_dividend_date']); $i++) {

				$ganpon = new payment();
				$ganpon->setAttrNo($last_id);
				$ganpon->setType('03');
				$ganpon->setPlannedDate($_POST['ganpon_dividend_date'][$i]);
				$ganpon->setCommission($_POST['ganpon_fee'][$i]);
				$ganpon->setPlannedAmount(floor( $_POST['hid_investment'] * (float)$_POST['ganpon_fee'][$i] / 100 ));

				insertPayment($pdo, $ganpon);
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
				$option->setStockPrice($_POST['stock_price'][$i]);
				$option->setOptionMemo($_POST['option_memo'][$i]);
				$option->setPlannedAmount(calcWithoutTax((int)$_POST['hid_investment'], (float)$_POST['option_fee'][$i]));

				insertPayment($pdo, $option);
			}

			// コミット
			$pdo->commit();

			// 「覚書管理メニュー」画面へ移動
			header ( 'Location: 31_mou_menu.php' );
			exit ();
		}

	// 初期表示
	} else {

		// プロジェクト属性
		$info = new project_attr();
	}

	// 投資家取得
	$investorList = getInvestorList($pdo, array());

	// 投資案件取得
	$projectList = getProjectList($pdo);

	// CSRF対策用トークン取得
	$csrf_token = setCSRFtoken();

} catch (PDOException $e) {
	echo "<script type=\"text/javascript\">alert(\'データベース接続・操作処理エラー\');</script>";
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
<title>覚書新規登録</title>
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
	<h1>覚書新規登録</h1>
	<dl>
		<dt>投資家名<span>Investor Name</span></dt>
		<dd>
			<select id="product" name="investor_no" required="required">
				<option value="">選択してください</option>
				<?php foreach ($investorList as $key => $val) { ?>
					<!--<option value="<?= h($key) ?>"><?= h($val) ?></option>-->
					<option value="<?= h($key) ?>" <?php if (strcmp($info->getInvestorNo(), $key) == 0) { echo 'selected'; } ?>>
						<?= h($val) ?>
					</option>
				<?php } ?>
			</select>
		</dd>

		<dt>投資案件名<span>project name</span></dt>
		<dd>
			<select id="project_no" name="project_no" required="required">
				<option value="">選択してください</option>
				<?php foreach ($projectList as $key => $val) { ?>
					<option value="<?= h($key) ?>"><?= h($val) ?></option>
					<!--
					<option value="<?= h($key) ?>" <?php if (strcmp($info->getProjectNo(), $key) == 0) { echo 'selected'; } ?>>
						<?= h($val) ?>
					</option>
					-->
				<?php } ?>
			</select>
		</dd>

		<dt>投資額<span>investment</span></dt>
		<dd>
			<input type="text" id="investment" name="investment" value="<?= h($info->getInvestmentAmount()) ?>" required="required" maxlength="16"/> 円<!--maxlength="19"-->
			<input type="hidden" id="hid_investment" name="hid_investment" value="" >
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
  						<span class="incNum"></span>回目
						配当日<input type="date" id="date" name="haito_dividend_date[]" value="" />
						配当率<input type="number" id="haito_fee" min="0" max="99.9" step="0.1" name="haito_fee[]" style="width: 14%;" value="" />％
						<ul id="thanks1">
							<li><span id="haito_amount" name="haito_amount[]" >0</span>円</li>
							<li><input type="button" class="haito_delete_button" value="削除" /></li>
						</ul>
					</td></tr>
				</tbody>
			</table>
		</dd>

		<dt>元本償還<span>principal repayment</span><input type="button" id="ganpon_add_button" value="元本償還追加"/></dt>
		<dd>
			<table id="ganpon">
				<tbody>
					<tr style="display: none;"><td>
							<span class="incNum"></span>回目
							償還日<input type="date" id="date" name="ganpon_dividend_date[]" value=""/>
							元本償還率<input type="number" min="0" max="100.0" step="0.1" id="ganpon_fee" name="ganpon_fee[]" style="width: 14%;" value=""/> ％
							<ul id="thanks1">
								<li><span class="ganpon_amount" name="ganpon_amount[]" >0</span>円</li>
								<li><input type="button" class="ganpon_delete_button" value="削除" /></li>
							</ul>
					</td></tr>
				</tbody>
			</table>
			<span id="ganpon_investment" name="ganpon_investment" style="display:none;">0</span><span style="display:none;">円</span>
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
							<!--<li><span id="option_amount"><?= h(number_format($val['plannedAmount'])) ?></span> 円</li>-->
							<li><input type="button" class="option_delete_button" value="削除" /></li>
						</ul>
					</td></tr>
				</tbody>
			</table>
		</dd>
	</dl>

	<p id="form_submit" class="center">
		<input type="submit" name="form_submit_button" id="form_submit_button" value="登録する" />
	</p>
	<p id="form_submit" class="right">
		<input type="button" id="form_cancel_button" value="覚書管理メニューへ戻る" onClick="location.href='31_mou_menu.php'">
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
	//$("#principal_to").val(data['endDate']);
}

/*
 * データ設定(配当)
 */
function setDataHaito(data) {

	$.each(data,function(index,obj){

		var haitoIndex = parseInt(index, 10) + 1;

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

	$.each(data,function(index,obj){

		var ganponIndex = parseInt(index, 10) + 1;

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

	$.each(data,function(index,obj){

		var optionIndex = parseInt(index, 10) + 1;

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

		// 株価設定
		var ele_stockPrice = $("input[name='stock_price[]']");
		ele_stockPrice.eq(optionIndex).val(obj['price']);

		// 内容設定
		var ele_optionMemo = $("textarea[name='option_memo[]']");
		ele_optionMemo.eq(optionIndex).val(obj['memo']);

		// 配当金設定
		ele_optionFee.eq(optionIndex).blur();
	})
}

//-->
</script>

<script>
jQuery(function($) {

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
		//$(".haito_delete_button").on("click", function() {
		$(".haito_delete_button").on("click", function(event,isClick=true) {
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
		$("#ganpon tbody tr:last-child").closest("tr").find('input[name="ganpon_fee[]"]').prop('required', true);

		//手動で追加ボタンが押された場合
		if(isClick){
			//"〇回目"の表示
			showCountGanpon();
			//元本償還総額の表示
			showGanponTotal();
		}

		// 行削除
		$(".ganpon_delete_button").on("click", function(event,isClick=true) {
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
		$("#option tbody tr:last-child").closest("tr").find('input[name="stock_price[]"]').prop('required', true);
		$("#option tbody tr:last-child").closest("tr").find('input[name="option_memo[]"]').prop('required', true);

		// 行削除
		$(".option_delete_button").on("click", function() {
			$(this).closest("tr").remove();
		});
	});

	// 自動計算(配当)
	$("#haito_fee").blur(function() {
		var investment = $("#hid_investment").val();
		var fee = $(this).val();
		$(this).closest("tr").find('#haito_amount').html(calcWithoutTax(investment, fee));
	});

	// 自動計算(元本返済)
	$("#ganpon_fee").blur(function() {
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

	// 自動計算(オプション)
	$("#option_fee").blur(function() {
		var investment = $("#hid_investment").val();
		var fee = $(this).val();
		$(this).closest("tr").find('#option_amount').html(calcWithoutTax(investment, fee));
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

		// 配当削除
		$(".haito_delete_button").each(function(index, elem) {
			if (index !== 0) {
				$(elem).click();
			}
		});

		// 元本返済削除
		$(".ganpon_delete_button").each(function(index, elem) {
			if (index !== 0) {
				$(elem).click();
			}
		});

		// オプション削除
		$(".option_delete_button").each(function(index, elem) {
			if (index !== 0) {
				$(elem).click();
			}
		});

		// プロジェクトNo取得
		var pNo = $('#project_no').val();
		if (pNo === '') {
			return false;
		}

		//Ajax通信(案件基本情報)
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
