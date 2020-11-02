<?php

// Excelファイル出力 宣言
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// コピー元の列数、行数
static $colOffset = 2;
static $rowOffset = 3;

// 1セットの列数、行数
static $colNum = 5;
static $rowNum = 12;

session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

//「ダウンロード」ボタン押下時
if (isset($_POST['form_submit_button'])) {

	// テンプレート読み込み
	$book = IOFactory::load('./template/temp.xlsx');
	$baseSheet = $book->getActiveSheet();

	try {

		// DB接続
		require_once 'DSN.php';
		$pdo = db_connect();

		// 投資家取得
		if (strcmp($_SESSION['loginUserType'], '02') == 0) {
			// オペレータ「スーパーバイザー」の場合、全投資家(一般、特別)が対象
			$sql = 'SELECT * FROM INVESTOR WHERE DELETE_FLG != \'1\'';
		} else {
			// オペレータ「ノーマル」の場合、全投資家(一般)が対象
			$sql = 'SELECT * FROM INVESTOR WHERE DELETE_FLG != \'1\' AND INVESTOR_TYPE = \'01\'';
		}

		$stmt = $pdo->prepare($sql);
		$stmt->execute();

		// 投資家毎のシート作成
		$sheetNum = 1;
		while ($row = $stmt->fetch()) {

			$sheet = $baseSheet->copy();
			//$sheet->setTitle($row['LAST_NAME'].$row['FIRST_NAME'], $sheetNum, false);
			$sheet->setTitle('('. $row['INVESTOR_NO'] .')'.$row['LAST_NAME'].$row['FIRST_NAME'], $sheetNum, false);
			$book->addSheet($sheet);

			// 案件を取得
			$sql2 =
				'SELECT '.
					'tab1.PROJ_ATTR_NO, '.
					'tab3.ITEM_NAME, '.
					'tab2.PROJECT_NAME '.
				'FROM '.
					'PROJECT_ATTRIBUTE tab1, '.
					'INVESTMENT_PROJECT tab2, '.
					'CODE_MASTER tab3 '.
				'WHERE '.
						'tab1.INVESTOR_NO = :investor_no '.
					'AND tab1.PROJECT_NO = tab2.PROJECT_NO '.
					'AND tab3.MASTER_CODE = \'03\' '.
					'AND tab3.ITEM_CODE = tab2.PROPOSAL_TYPE ';
			$stmt2 = $pdo->prepare($sql2);
			$stmt2->bindParam(':investor_no', $row['INVESTOR_NO'], PDO::PARAM_INT);
			$stmt2->execute();

			// 案件をコピー
			$projNum = 1;
			$projN0Array = array();
			while ($row2 = $stmt2->fetch()) {

				// タイトル設定
				setTitle($sheet, $projNum, $row2);

				// プロジェクト属性No設定
				array_push($projN0Array, $row2['PROJ_ATTR_NO']);

				$projNum++;
			}

			// 案件がない場合は削除
			if ($projNum == 1) {
				// フォーマットシートの削除
				//$sheetIndex = $book->getIndex($book->getSheetByName($row['LAST_NAME'].$row['FIRST_NAME']));
				$sheetIndex = $book->getIndex($book->getSheetByName('('. $row['INVESTOR_NO'] .')'.$row['LAST_NAME'].$row['FIRST_NAME']));
				
				$book->removeSheetByIndex($sheetIndex);
				continue;
			}

			// 年月毎
			$Month = $_POST['term_from'];
			$termTo = date('Y-m-d', strtotime('last day of ' . $_POST['term_to']));
			$monthStartCol = 6;
			$maxColCntbyMonth = 1;
			while ($Month <= $termTo) {

				$startDate = date('Y-m-d', strtotime('first day of ' . $Month));
				$endDate = date('Y-m-d', strtotime('last day of ' . $Month));

				$projNum = 1;
				foreach ($projN0Array as &$value) {

					// 配当
					{
						// 検索
						$sql3 =
							'SELECT '.
								'* '.
							'FROM '.
								'PAYMENT_MANAGEMENT '.
							'WHERE '.
									'PROJ_ATTR_NO = :proj_attr_no '.
								'AND PLANNED_PAYMENT_DATE >= :start_date '.
								'AND PLANNED_PAYMENT_DATE <= :end_date '.
								'AND PAYMENT_TYPE = \'01\' '.
							'ORDER BY '.
								'PLANNED_PAYMENT_DATE';
						$stmt3 = $pdo->prepare($sql3);
						$stmt3->bindParam(':proj_attr_no', $value, PDO::PARAM_INT);
						$stmt3->bindParam(':start_date', $startDate, PDO::PARAM_STR);
						$stmt3->bindParam(':end_date', $endDate, PDO::PARAM_STR);
						$stmt3->execute();

						// 出力
						$dateCol = 0;
						while ($row3 = $stmt3->fetch()) {

							$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum, date('d日',  strtotime($row3['PLANNED_PAYMENT_DATE'])));
							$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 1, $row3['PLANNED_PAY_AMOUNT']);

							if (!is_null($row3['ACTUAL_PAYMENT_DATE'])) {
								$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 2, date('d日',  strtotime($row3['ACTUAL_PAYMENT_DATE'])));
								$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 3, $row3['ACTUAL_PAY_AMOUNT']);
							}

							$dateCol++;
						}

						// 月毎の最大列数の取得
						if ($dateCol > $maxColCntbyMonth) {
							$maxColCntbyMonth = $dateCol;
						}
					}

					// オプション
					{
						$sql4 =
							'SELECT '.
								'* '.
							'FROM '.
								'PAYMENT_MANAGEMENT '.
							'WHERE '.
									'PROJ_ATTR_NO = :proj_attr_no '.
								'AND PLANNED_PAYMENT_DATE >= :start_date '.
								'AND PLANNED_PAYMENT_DATE <= :end_date '.
								'AND PAYMENT_TYPE = \'02\' '.
							'ORDER BY '.
								'PLANNED_PAYMENT_DATE';
						$stmt4 = $pdo->prepare($sql4);
						$stmt4->bindParam(':proj_attr_no', $value, PDO::PARAM_INT);
						$stmt4->bindParam(':start_date', $startDate, PDO::PARAM_STR);
						$stmt4->bindParam(':end_date', $endDate, PDO::PARAM_STR);
						$stmt4->execute();

						// 出力
						$dateCol = 0;
						while ($row4 = $stmt4->fetch()) {
							$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 8, date('d日',  strtotime($row4['PLANNED_PAYMENT_DATE'])));
							$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 9, $row4['PLANNED_PAY_AMOUNT']);

							if (!is_null($row4['ACTUAL_PAYMENT_DATE'])) {
								$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 10, date('d日',  strtotime($row4['ACTUAL_PAYMENT_DATE'])));
								$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 11, $row4['ACTUAL_PAY_AMOUNT']);
							}

							$dateCol++;
						}

						if ($dateCol > $maxColCntbyMonth) {
							$maxColCntbyMonth = $dateCol;
						}
					}

					// 元本
					{
						$sql5 =
							'SELECT '.
								'* '.
							'FROM '.
								'PAYMENT_MANAGEMENT '.
							'WHERE '.
									'PROJ_ATTR_NO = :proj_attr_no '.
								'AND PLANNED_PAYMENT_DATE >= :start_date '.
								'AND PLANNED_PAYMENT_DATE <= :end_date '.
								'AND PAYMENT_TYPE = \'03\' '.
							'ORDER BY '.
								'PLANNED_PAYMENT_DATE';
						$stmt5 = $pdo->prepare($sql5);
						$stmt5->bindParam(':proj_attr_no', $value, PDO::PARAM_INT);
						$stmt5->bindParam(':start_date', $startDate, PDO::PARAM_STR);
						$stmt5->bindParam(':end_date', $endDate, PDO::PARAM_STR);
						$stmt5->execute();

						// 出力
						$dateCol = 0;
						while ($row5 = $stmt5->fetch()) {
							$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 4, date('d日',  strtotime($row5['PLANNED_PAYMENT_DATE'])));
							$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 5, $row5['PLANNED_PAY_AMOUNT']);

							if (!is_null($row5['ACTUAL_PAYMENT_DATE'])) {
								//$sheet->setCellValueByColumnAndRow($dateCol, $rowOffset + $rowNum * $projNum + 6, date('d日',  strtotime($row5['ACTUAL_PAYMENT_DATE'])));
								//$sheet->setCellValueByColumnAndRow($dateCol, $rowOffset + $rowNum * $projNum + 7, $row5['ACTUAL_PAY_AMOUNT']);
								$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 6, date('d日',  strtotime($row5['ACTUAL_PAYMENT_DATE'])));
								$sheet->setCellValueByColumnAndRow($monthStartCol + $dateCol, $rowOffset + $rowNum * $projNum + 7, $row5['ACTUAL_PAY_AMOUNT']);
							}

							$dateCol++;
						}
					}

					if ($dateCol > $maxColCntbyMonth) {
						$maxColCntbyMonth = $dateCol;
					}

					$projNum++;
				}

				// 年月設定
				$sheet->setCellValueByColumnAndRow($monthStartCol, 2, date('Y年m月', strtotime($Month)));
				$sheet->mergeCellsByColumnAndRow($monthStartCol, 2, $monthStartCol + $maxColCntbyMonth - 1, 2);

				// 次の月のスタート列数を更新
				$monthStartCol = $monthStartCol + $maxColCntbyMonth;
				$maxColCntbyMonth = 1;

				$Month = date('Y-m-d', strtotime($Month . ' +1 month'));
			}

			// スタイル
			$style = $sheet->getStyle('F2:'.Coordinate::stringFromColumnIndex($monthStartCol-1).($rowOffset + $rowNum * $projNum - 1));
			$style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN );
			$style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

			// フォーマット列の削除
			$sheet->removeRow(3, 12);
			$sheet->setSelectedCell('A1');

			$sheetNum++;

		}

		// フォーマットシートの削除
		$book->removeSheetByIndex(0);

		// ファイル名
		$fineName = "個別配当表".date("Ymd-His").".xlsx";

		// ファイルダウンロード
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="'.$fineName.'"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		ob_end_clean(); //バッファ消去

		$writer = new Xlsx($book);
		$writer->save('php://output');
		exit();

	} catch (PDOException $e) {
		echo '<script type="text/javascript">alert("データベース接続・操作処理エラー")</script>';
	}

}

function setTitle($sheet, $projNum, $row2) {

	// コピー元の列数、行数
	global $colOffset;
	global $rowOffset;

	// 1セットの列数、行数
	global $colNum;
	global $rowNum;

	for ($col=0; $col<$colNum; $col++) {

		for ($row=0; $row<$rowNum; $row++) {

			// セルを取得
			$cell = $sheet->getCellByColumnAndRow($colOffset + $col, $rowOffset + $row);
			// セルスタイルを取得
			$style = $sheet->getStyleByColumnAndRow($rowOffset + $col, $rowOffset + $row);

			// コピー先のセル(数値から列文字列に変換)
			$offsetCell = Coordinate::stringFromColumnIndex($colOffset + $col) . (string)($rowOffset + $row + $rowNum * $projNum);

			// セル値をコピー
			$sheet->setCellValue($offsetCell, $cell->getValue());
			// スタイルをコピー
			$sheet->duplicateStyle($style, $offsetCell);

		}
	}

	// 種別
	$sheet->setCellValue('B'.($rowOffset + $rowNum*$projNum), $row2['ITEM_NAME']);
	$sheet->mergeCells('B'.($rowOffset + $rowNum*$projNum).':B'.($rowOffset + $rowNum*$projNum+$rowNum-1));

	// 案件名
	$sheet->setCellValue('C'.($rowOffset + $rowNum*$projNum), $row2['PROJECT_NAME']);
	$sheet->mergeCells('C'.($rowOffset + $rowNum*$projNum).':C'.($rowOffset + $rowNum*$projNum+$rowNum-1));

	// 支払タイプ
	$sheet->mergeCells('D'.($rowOffset + $rowNum*$projNum).':D'.($rowOffset + $rowNum*$projNum + 3));
	$sheet->mergeCells('D'.($rowOffset + $rowNum*$projNum + 4).':D'.($rowOffset + $rowNum*$projNum + 7));
	$sheet->mergeCells('D'.($rowOffset + $rowNum*$projNum + 8).':D'.($rowOffset + $rowNum*$projNum + 11));

	// 予定・実績
	$sheet->mergeCells('E'.($rowOffset + $rowNum*$projNum).':E'.($rowOffset + $rowNum*$projNum + 1));
	$sheet->mergeCells('E'.($rowOffset + $rowNum*$projNum + 2).':E'.($rowOffset + $rowNum*$projNum + 3));
	$sheet->mergeCells('E'.($rowOffset + $rowNum*$projNum + 4).':E'.($rowOffset + $rowNum*$projNum + 5));
	$sheet->mergeCells('E'.($rowOffset + $rowNum*$projNum + 6).':E'.($rowOffset + $rowNum*$projNum + 7));
	$sheet->mergeCells('E'.($rowOffset + $rowNum*$projNum + 8).':E'.($rowOffset + $rowNum*$projNum + 9));
	$sheet->mergeCells('E'.($rowOffset + $rowNum*$projNum + 10).':E'.($rowOffset + $rowNum*$projNum + 11));

	$style = $sheet->getStyle('E'.($rowOffset + $rowNum*$projNum).':E'.($rowOffset + $rowNum*$projNum + 11));
	$style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<title>個別配当表出力</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" href="css/mailform.css" />
<link rel="stylesheet" href="css/thanks.css" />

<!-- ご希望の日時選択ライブラリここから -->
<link rel="stylesheet" href="css/jquery.datetimepicker.css" />
<!-- ご希望の日時選択ライブラリここまで -->
</head>
<body>
<div id="main">
<form action="" method="post" id="mail_form">

	<h1>個別配当表出力</h1>
	<dl>
		<dt>ダウンロード<span>Download</span></dt>
		<dd>
			<input type="month" name="term_from" id="term_from" required="required">
			 ～ 
			<input type="month" name="term_to" id="term_to" required="required">
		</dd>
	</dl>
	<p class="center">
		<input type="submit" name="form_submit_button" id="form_submit_button" value="ダウンロード" />
	</p>
	<p class="right">
		<input id="form_cancel_button" type="button" value="投資家・投資案件管理メニューへ戻る" onClick="location.href='03_menu.php'">
	</p>
	<p class="right" >
		<input type="button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>

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

	//日付Validation
	$("#form_submit_button").on("click", function(){
		$("#term_from").css("background-color", "");
		$("#term_to").css("background-color", "");
		var term_from = new Date($("#term_from").val());
		var term_to = new Date($("#term_to").val());
		if ( term_from > term_to ) {
			$("#term_from").css("background-color", "#FFC0CB");
			$("#term_to").css("background-color", "#FFC0CB");
			alert("対象期間の入力内容を確認してください。");
			return false;
		}
	});

});
</script>

</body>
</html>
