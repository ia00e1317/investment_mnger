<?php

// Excelファイル出力
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

// 非ログイン状態、又はセッションタイムアウト状態でこのページを表示しようとした場合、ログアウト画面に飛ばす
if (!isset($_SESSION['loginUserID'])) {
	header('Location: 02_logout.php');
	exit();
}

// ダウンロードボタン押下
if (isset($_POST['form_submit_button'])) {

	try {

		// DB接続
		require_once 'DSN.php';
		$pdo = db_connect();

		$sql = 'SELECT * FROM INVESTOR WHERE INTRODUCED_INVESTOR = \'\'';
		$stmt = $pdo->prepare($sql);
		$stmt->execute();

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		$out_col_no = 2;
		while ($row = $stmt->fetch()) {

			// 自分自身を出力
			$sheet->setCellValueByColumnAndRow(
					1, $out_col_no, $row['INVESTOR_NO'].' '.$row['LAST_NAME'].$row['FIRST_NAME']);

			// 以下階層を出力
			$out_line_count = excelOutput($pdo, $sheet, 2, $out_col_no, $row['INVESTOR_NO']);

			// 次の出力行数を算出
			$out_col_no = $out_col_no + $out_line_count + 1;
			if ($out_line_count < 1) {
				$out_col_no++;
			}
		}

		// ファイル名
		$fineName = "投資家相関図".date("Ymd-His").".xlsx";

		// ファイルダウンロード
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename="'.$fineName.'"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		ob_end_clean(); //バッファ消去

		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
		exit();

	} catch (PDOException $e) {
		echo '<script type="text/javascript">alert("データベース接続・操作処理エラー")</script>';
	} finally {
		$stmt = null;
		$pdo = null;
	}

}

/**
 * excelに出力する。
 *
 * @param PDO $pdo DBコネクション
 * @param Spreadsheet $sheet スプレッドシート
 * @param mixed $kaiso 階層
 * @param mixed $start_col 出力開始行
 * @param mixed $id 投資家ID
 * @throws Exception
 * @return number
 */
function excelOutput(PDO $pdo, $sheet, $kaiso, $start_row, $id) {

	$sql = 'SELECT * FROM INVESTOR WHERE INTRODUCED_INVESTOR = :investor_id';

	try {

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':investor_id', $id, PDO::PARAM_STR);
		$stmt->execute();

		$num = 0;
		while ($row = $stmt->fetch()) {

			// 自分自身を出力
			$sheet->setCellValueByColumnAndRow(
					$kaiso, $start_row + $num, $row['INVESTOR_NO'].' '.$row['LAST_NAME'].$row['FIRST_NAME']);

			// 自分配下を出力
			$line = excelOutput($pdo, $sheet, $kaiso + 1, $start_row + $num, $row['INVESTOR_NO']);

			$num = $num + $line;
			if ($line < 1) {
				$num++;
			}

		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $num;
}

?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<title>投資家相関図出力</title>
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
    <h1>投資家相関図出力</h1>

	<p class="center">
		<input type="submit" name="form_submit_button" value="ダウンロード" />
	</p>
	<p class="right">
		<input id="form_cancel_button" type="button" value="投資家・投資案件管理メニューへ戻る" onClick="location.href='03_menu.php'">
    </p>
	<p class="right">
		<input type="button" id="form_logout_button" value="ログアウト" onClick="location.href='02_logout.php'" />
	</p>
</form>
</div>

</body>
</html>
