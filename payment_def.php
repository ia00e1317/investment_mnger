<?php

class payment_def {

	private $transNo = "";
	private $projectNo = "";
	private $type = "";
	private $termFrom = "";
	private $termTo = "";
	private $plannedDate = "";
	private $commission = "";
	private $price = "";
	private $memo = "";


	/**
	 * @return string
	 */
	public function getTransNo() {
		return $this->transNo;
	}

	/**
	 * @return string
	 */
	public function getProjectNo() {
		return $this->projectNo;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getTermFrom() {
		return $this->termFrom;
	}

	/**
	 * @return string
	 */
	public function getTermTo() {
		return $this->termTo;
	}

	/**
	 * @return string
	 */
	public function getPlannedDate() {
		return $this->plannedDate;
	}

	/**
	 * @return string
	 */
	public function getCommission() {
		return $this->commission;
	}

	/**
	 * @return string
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * @return string
	 */
	public function getMemo() {
		return $this->memo;
	}

	
	/**
	 * @param string $transNo
	 */
	public function setTransNo($transNo) {
		$this->transNo = $transNo;
	}

	/**
	 * @param string $projectNo
	 */
	public function setProjectNo($projectNo) {
		$this->projectNo = $projectNo;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @param string $termFrom
	 */
	public function setTermFrom($termFrom) {
		$this->termFrom = $termFrom;
	}

	/**
	 * @param string $termTo
	 */
	public function setTermTo($termTo) {
		$this->termTo = $termTo;
	}

	/**
	 * @param string $plannedDate
	 */
	public function setPlannedDate($plannedDate) {
		$this->plannedDate = $plannedDate;
	}

	/**
	 * @param string $commission
	 */
	public function setCommission($commission) {
		$this->commission = $commission;
	}

	/**
	 * @param string $commission
	 */
	public function setPrice($price) {
		$this->price = $price;
	}

	/**
	 * @param string $commission
	 */
	public function setMemo($memo) {
		$this->memo = $memo;
	}

	public function toArray() {

		$payment = array();
		$payment['transNo'] = $this->transNo;
		$payment['projectNo'] = $this->projectNo;
		$payment['type'] = $this->type;
		$payment['termFrom'] = $this->termFrom;
		$payment['termTo'] = $this->termTo;
		$payment['plannedDate'] = $this->plannedDate;
		$payment['commission'] = $this->commission;
		$payment['price'] = $this->price;
		$payment['memo'] = $this->memo;

		return $payment;
	}

}

/**
 * 支払タイプ毎の支払定義リストを取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no プロジェクト番号
 * @param mixed $type 支払タイプ
 * @throws Exception
 * @return ArrayObject
 */
function getPaymentDefList(PDO $pdo, $no, $type) {

	$sql =
		'SELECT '.
			'* '.
		'FROM '.
			'PAYMENT_DEFINE '.
		'WHERE '.
				'PROJECT_NO = :proj_no '.
			'AND PAYMENT_TYPE = :type '.
		'ORDER BY '.
			'PLANNED_PAYMENT_DATE';

	$list = array();
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':proj_no', $no, PDO::PARAM_INT);
		$stmt->bindParam(':type', $type, PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {

			$info = new payment_def();
			$info->setTransNo($row['TRANSACTION_NO']);
			$info->setProjectNo($row['PROJECT_NO']);
			$info->setType($row['PAYMENT_TYPE']);
			$info->setTermFrom($row['TERM_FROM']);
			$info->setTermTo($row['TERM_TO']);
			$info->setPlannedDate($row['PLANNED_PAYMENT_DATE']);
			$info->setCommission($row['COMMISION']);
			$info->setPrice($row['PRICE']);
			$info->setMemo($row['MEMO']);

			array_push($list, $info->toArray());
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return new ArrayObject($list);
}

/**
 * 支払定義を登録する。
 *
 * @param PDO $pdo DBコネクション
 * @param payment $info 支払予実
 * @throws Exception
 */
function insertPaymentDef(PDO $pdo, payment_def $info) {

	$sql =
		'INSERT INTO PAYMENT_DEFINE(' .
			'PROJECT_NO, ' .			// プロジェクト番号
			'PAYMENT_TYPE, ' .			// 支払タイプ
			'TERM_FROM, '		.		// 支払予定日
			'TERM_TO, ' .				// 支払タイプ
			'PLANNED_PAYMENT_DATE, ' .	// 支払予定日
			'COMMISION, ' .				// 手数料
			'PRICE, ' .					// 
			'MEMO ' .					// 
		') VALUES (' .
			':proj_no, '.
			':type, '.
			':term_from, '.
			':term_to, '.
			':dividend_date, ' .
			':dividend_commission, ' .
			':stock_price, ' .			// 
			':option_memo ' .			// 
		')';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':proj_no', $info->getProjectNo(), PDO::PARAM_INT);
		$stmt->bindParam(':type', $info->getType(), PDO::PARAM_STR);
		$stmt->bindParam(':term_from', $info->getTermFrom(), PDO::PARAM_STR);
		$stmt->bindParam(':term_to', $info->getTermTo(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_date', $info->getPlannedDate(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_commission', $info->getCommission(), PDO::PARAM_STR);
		$stmt->bindParam(':stock_price', $info->getPrice(), PDO::PARAM_INT);
		$stmt->bindParam(':option_memo', $info->getMemo(), PDO::PARAM_STR);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}
}

/**
 * 支払定義を更新する。
 *
 * @param PDO $pdo DBコネクション
 * @param payment $info 支払予実
 * @throws Exception
 */
function updatePaymentDef(PDO $pdo, payment_def $info) {

	$sql =
		'UPDATE '.
			'PAYMENT_DEFINE ' .
		'SET ' .
			'TERM_FROM = :term_from, ' .				// 支払予定日
			'TERM_TO = :term_to, ' .					// 手数料
			'PLANNED_PAYMENT_DATE = :dividend_date, ' .	// 支払予定日
			'COMMISION = :dividend_commission, ' .		// 手数料
			'PRICE = :stock_price, ' .					// 
			'MEMO = :option_memo ' .					// 
		'WHERE ' .
			'TRANSACTION_NO = :trans_no';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':term_from', $info->getTermFrom(), PDO::PARAM_STR);
		$stmt->bindParam(':term_to', $info->getTermTo(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_date', $info->getPlannedDate(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_commission', $info->getCommission(), PDO::PARAM_STR);
		$stmt->bindParam(':stock_price', $info->getPrice(), PDO::PARAM_INT);
		$stmt->bindParam(':option_memo', $info->getMemo(), PDO::PARAM_STR);
		$stmt->bindParam(':trans_no', $info->getTransNo(), PDO::PARAM_INT);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * 支払定義を削除する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no トランザクション番号
 * @throws Exception
 */
function deletePaymentDef(PDO $pdo, $no) {

	$sql =
		'DELETE FROM '.
			'PAYMENT_DEFINE '.
		'WHERE '.
			'TRANSACTION_NO = :trans_no';

	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':trans_no', $no, PDO::PARAM_INT);
		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

?>
