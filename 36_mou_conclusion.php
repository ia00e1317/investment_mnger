<?php
session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (! isset($_SESSION['loginUserID'])) {
    header('Location: 02_logut.php');
    exit();
}

require_once 'DSN.php';
require_once 'project_attr.php';
require_once 'payment.php';
require_once 'security.php';
require_once 'investor.php';
require_once 'project.php';

ob_start();

try {

	// DB接続
	$pdo = db_connect();

	// 締結ボタン「form_submit_button」が押された際の処理
	if (isset($_POST['imagedata'])) {

		// CSRF対策チェック
		if (!checkCSRFtoken()) {
			header('Location: 02_logout.php');
			exit();
		}

		// 締結
		conclusion($pdo, $_SESSION['proj_attr_no'], $_POST['imagedata']);

		// セッションクリア
		unset($_SESSION['proj_attr_no']);

		//「覚書管理メニュー」画面へ移動
		header ( 'Location: 35_mou_conclusion_list.php' );
		exit ();

	// 初期表示
	} else {

		// 画面情報取得
		$projAtt = getProjectAttr($pdo, $_POST['proj_attr_no']);				// プロジェクト属性
		$haitoList = getPaymentList($pdo, $_POST['proj_attr_no'], '01');		// 配当
		$ganponList = getPaymentList($pdo, $_POST['proj_attr_no'], '03');		// 元本
		$optionList = getPaymentList($pdo, $_POST['proj_attr_no'], '02');		// オプション
		//$principal = getPaymentList($pdo, $_POST['proj_attr_no'], '03')[0];		//

		// 画面情報取得
		$projInfo = getProjectInfo($pdo, $projAtt->getProjectNo());			// 投資案件情報
		$investorInfo = getInvestorInfo($pdo, $projAtt->getInvestorNo());	// 資金提供者情報
		$fundInfo =  getFundInfo($pdo);										// 資金管理者情報

		//覚書タイトル生成
		$attr_title = '(物販とその他)';
		if( strcmp($projInfo->getType(), '01') == 0 ){
			$attr_title = '(再生事業全般)';
		}

		// セッション登録
		$_SESSION['proj_attr_no'] = $_POST['proj_attr_no'];
	}

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
<title>覚書情報締結</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" href="css/mailform.css" />
<link rel="stylesheet" href="css/thanks.css" />
<script src="libs/jSignature.min.js"></script>
<style type="text/css">
	div#main h1.title { border-bottom: none; }
	.oboegaki { text-align: right; width: 95%; }
	.title_block { border-bottom: 1px solid #454545; width: 90%; margin: 0 auto; }
	.date { text-align: right; }

	@media print{
		.noprint {
			display: none;
		}
	}
/*
	@page {	margin: 0; }
*/
</style>
</head>
<body>
<div id="main">
<form action="" method="post" id="mail_form" name="mainForm">
	<div class="oboegaki">
		<span><?= h($projAtt->getAttrCode()) ?></span>
	</div>
	<div class="title_block">
		<h1 class="title">
			<span>覚書 <?= $attr_title ?></span>
		</h1>
		<div class="date">
			<span><?= h( date('Y年m月d日',  strtotime($projInfo->getStartDate()))) ?></span>
		</div>
	</div>
	<dl>
		<dt>資金提供者<span>Investor Name</span></dt>
		<dd><?= h($projAtt->getInvestorName()) ?>　様 <br>（住所）<?= h($investorInfo->getAddress()) ?></dd>
		<dt>投資案件名<span>project name</span></dt>
		<dd><?= h('('.$projInfo->getTypeName().') ' .$projInfo->getName()) ?></dd>
		<dt>資金提供額<span>investment</span></dt>
		<dd><?= number_format($projAtt->getInvestmentAmount()) ?>円</dd>
		<dt></dt>
		<dd><?= h($projInfo->getMemorandum()) ?></dd>
		<dt>資金預かり日<span>start date</span></dt>
		<dd><?= date('Y年n月j日', strtotime($projAtt->getStartDate())) ?></dd>
		<dt>終了日<span>end date</span></dt>
		<dd><?= date('Y年n月j日', strtotime($projAtt->getEndDate())) ?></dd>

		<div <?php if ( count($haitoList) == 0 ) { echo 'style="display:none;"'; } ?>>
			<dt>配当<span>dividend</span></dt>
			<dd>
				<table id="haito">
				<tbody>
					<?php $count1 = 0; ?>
					<?php foreach ($haitoList as $val) { ?>
						<tr><td>
							<span><?php echo ++$count1."回目　"; ?></span>
							配当日　<?= date('Y年n月j日', strtotime($val['plannedDate'])) ?>
							配当率　 <?= $val['commission'] ?> ％
							<ul id="thanks1" style="margin-top: 0;">
								<li><span id="haito_amount"><?= number_format($val['plannedAmount']) ?></span> 円</li>
								<!--<li><input type="button" class="haito_delete_button" value="削除" /></li> -->
							</ul>
						</td></tr>
					<?php } ?>
				</tbody>
				</table>
			</dd>
		</div>

		<div <?php if ( count($ganponList) == 0 ) { echo 'style="display:none;"'; } ?>>
			<dt>元本償還<span>principal repayment</span></dt>
			<dd>
				<table id="ganpon">
				<tbody>
					<?php $count2 = 0; ?>
					<?php foreach ($ganponList as $val) { ?>
						<tr><td>
							<span><?php echo ++$count2."回目　"; ?></span>
							償還日　<?= date('Y年n月j日', strtotime($val['plannedDate'])) ?>
							元本償還率　 <?= $val['commission'] ?> ％
							<ul id="thanks1" style="margin-top: 0;">
								<li><span class="ganpon_amount"><?= number_format($val['plannedAmount']) ?></span> 円</li>
								<!--<li><input type="button" class="ganpon_delete_button" value="削除" /></li> -->
							</ul>
						</td></tr>
					<?php } ?>
				</tbody>
				</table>
				<ul id="thanks2">
					<li style="display:none;"><?= number_format($projAtt->getInvestmentAmount()) ?>円</li>
				</ul>
			</dd>
		</div>

		<div <?php if ( count($optionList) == 0 ) { echo 'style="display:none;"'; } ?>>
			<dt>オプション<span>Option</span></dt>
			<dd>
				<table id="option">
				<tbody>
					<?php foreach ($optionList as $val) { ?>
						<tr><td>
							<?= date('Y年n月j日', strtotime($val['termFrom'])) ?>
								～ 
							<?= date('Y年n月j日', strtotime($val['termTo'])) ?>
							<br>
							配当日 <?= date('Y年n月j日', strtotime($val['plannedDate'])) ?>　
							配当率 <?= $val['commission'] ?> ％　
							株価 <?= $val['stockPrice'] ?> 円
							<br>
							【株価×株数】円　【株数(根拠不明)】株
							<br>
							<b><?= $val['optionMemo'] ?></b>
							<ul id="thanks1" style="margin-top: 0;">
								<!--<li><span class="option_amount"><?php echo number_format($val['plannedAmount']); ?></span> 円</li>-->
							</ul>
						</td></tr>
					<?php } ?>
				</tbody>
				</table>
			</dd>
		</div>

		<dt>資金管理者<span>fund manager</span></dt>
		<dd>（住所）<span><?= h($fundInfo['fundMgrAdd']) ?></span><br>
		（名前）<span><?= h($fundInfo['fundMgrName']) ?></span>
		</dd>
	</dl>

	<?php if (strcmp($projAtt->getContFlg(), '1') == 0) {
		echo '<p id="form_submit" class="center">';
		$imginfo = getimagesize('data:application/octet-stream;base64,' . $projAtt->getSign());
		echo '<img src="data:' . $imginfo['mime'] . ';base64,'.$projAtt->getSign().'" width="560" height="100">';
		echo '</p>';
		  } else { ?>
		<p id="form_submit" class="center">
			<span id="sign" style="color: red">本投資案件に同意される場合、サインをお願いします。</span>
			<canvas id="signature" width="560" height="100" style="border: 2px solid;"></canvas>
		</p>
		<p id="form_submit" class="center noprint">
			<input type="button" id="form_submit_button" value="締結する" onclick="conclusion();"/>
		</p>
	<?php } ?>
	<p id="form_submit" class="center noprint">
		<input type="button" value="印刷" class="noprint" onclick="window.print();"/>
	</p>
	<p id="form_submit" class="right noprint">
		<input type="button" id="form_cancel_button" value="戻る" onClick="location.href='35_mou_conclusion_list.php'">
	</p>
	<p id="form_submit" class="right noprint">
		<input type="button" id="form_submit_button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>

	<input type="hidden" name="imagedata" value="">
	<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
</form>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>

<script>

var canvas = document.getElementById('signature'),
	ctx = canvas.getContext('2d'),
	moveflg = 0,
	Xpoint,
	Ypoint;

//初期値（サイズ、色、アルファ値）の決定
var defSize = 2,
	defColor = "#555";

//ストレージの初期化
var myStorage = localStorage;
window.onload = initLocalStorage();

//PC対応
canvas.addEventListener('mousedown', startPoint, false);
canvas.addEventListener('mousemove', movePoint, false);
canvas.addEventListener('mouseup', endPoint, false);
//スマホ対応
canvas.addEventListener('touchstart', startPoint, false);
canvas.addEventListener('touchmove', movePoint, false);
canvas.addEventListener('touchend', endPoint, false);

function startPoint(e){

	e.preventDefault();
	ctx.beginPath();

	Xpoint = e.layerX;
	Ypoint = e.layerY;

	ctx.moveTo(Xpoint, Ypoint);
}

function movePoint(e){

	if(e.buttons === 1 || e.witch === 1 || e.type == 'touchmove'){
		Xpoint = e.layerX;
		Ypoint = e.layerY;
		moveflg = 1;

		ctx.lineTo(Xpoint, Ypoint);
		ctx.lineCap = "round";
		ctx.lineWidth = defSize * 2;
		ctx.strokeStyle = defColor;
		ctx.stroke();
	}
}

function endPoint(e){

	if(moveflg === 0){
		ctx.lineTo(Xpoint-1, Ypoint-1);
		ctx.lineCap = "round";
		ctx.lineWidth = defSize * 2;
		ctx.strokeStyle = defColor;
		ctx.stroke();
	}

	moveflg = 0;
	setLocalStoreage();
}

function clearCanvas(){

	if(confirm('Canvasを初期化しますか？')){
		initLocalStorage();
		temp = [];
		resetCanvas();
	}
}

function resetCanvas() {
	ctx.clearRect(0, 0, ctx.canvas.clientWidth, ctx.canvas.clientHeight);
}

function initLocalStorage(){
	myStorage.setItem("__log", JSON.stringify([]));
}

function setLocalStoreage(){

	var png = canvas.toDataURL();
	var logs = JSON.parse(myStorage.getItem("__log"));

	setTimeout(function(){
		logs.unshift({0:png});
		myStorage.setItem("__log", JSON.stringify(logs));

		currentCanvas = 0;
		temp = [];
	}, 0);
}

function draw(src) {
	var img = new Image();
	img.src = src;

	img.onload = function() {
		ctx.drawImage(img, 0, 0);
	}
}

function conclusion() {

	var canvas = document.getElementById("signature");

	var image_data = canvas.toDataURL("image/png").replace(/^.*,/, '');
	document.mainForm.imagedata.value = image_data;

	document.mainForm.submit();
}

</script>

</body>
</html>
