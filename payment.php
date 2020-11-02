<?php

class payment {

	private $type = "";
	private $transNo = "";
	private $attrNo = "";
	private $termFrom = "";
	private $termTo = "";
	private $plannedDate = "";
	private $plannedAmount = "";
	private $commission = "";
	private $actualDate = "";
	private $actualAmount ="";
	private $stockPrice = "";
	private $optionMemo ="";


	/**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

	/**
     * @return string
     */
    public function getTransNo()
    {
        return $this->transNo;
    }

	/**
     * @return string
     */
    public function getAttrNo()
    {
        return $this->attrNo;
    }

	/**
     * @return string
     */
    public function getTermFrom()
    {
        return $this->termFrom;
    }

	/**
     * @return string
     */
    public function getTermTo()
    {
        return $this->termTo;
    }

	/**
     * @return string
     */
    public function getPlannedDate()
    {
        return $this->plannedDate;
    }

	/**
     * @return string
     */
    public function getPlannedAmount()
    {
        return $this->plannedAmount;
    }

	/**
     * @return string
     */
    public function getCommission()
    {
        return $this->commission;
    }

	/**
     * @return string
     */
    public function getActualDate()
    {
        return $this->actualDate;
    }

	/**
     * @return string
     */
    public function getActualAmount()
    {
        return $this->actualAmount;
    }

	/**
	 * @return string
	 */
	public function getStockPrice()
	{
		return $this->stockPrice;
	}

	/**
	 * @return string
	 */
	public function getOptionMemo()
	{
		return $this->optionMemo;
	}


	/**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

	/**
     * @param string $transNo
     */
    public function setTransNo($transNo)
    {
        $this->transNo = $transNo;
    }

	/**
     * @param string $attrNo
     */
    public function setAttrNo($attrNo)
    {
        $this->attrNo = $attrNo;
    }

	/**
     * @param string $termFrom
     */
    public function setTermFrom($termFrom)
    {
        $this->termFrom = $termFrom;
    }

	/**
     * @param string $termTo
     */
    public function setTermTo($termTo)
    {
        $this->termTo = $termTo;
    }

	/**
     * @param string $plannedDate
     */
    public function setPlannedDate($plannedDate)
    {
        $this->plannedDate = $plannedDate;
    }

	/**
     * @param string $plannedAmount
     */
    public function setPlannedAmount($plannedAmount)
    {
        $this->plannedAmount = $plannedAmount;
    }

	/**
     * @param string $commission
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;
    }

	/**
     * @param string $actualDate
     */
    public function setActualDate($actualDate)
    {
        $this->actualDate = $actualDate;
    }

	/**
     * @param string $actualAmount
     */
    public function setActualAmount($actualAmount)
    {
        $this->actualAmount = $actualAmount;
    }

	/**
     * @param string $actualAmount
     */
    public function setStockPrice($stockPrice)
    {
        $this->stockPrice = $stockPrice;
	}
	
	/**
     * @param string $actualAmount
     */
    public function setOptionMemo($optionMemo)
    {
        $this->optionMemo = $optionMemo;
    }


	public function toArray() {

		$payment = array();
		$payment['attrNo'] = $this->attrNo;
		$payment['transNo'] = $this->transNo;
		$payment['type'] = $this->type;
		$payment['termFrom'] = $this->termFrom;
		$payment['termTo'] = $this->termTo;
		$payment['plannedDate'] = $this->plannedDate;
		$payment['plannedAmount'] = $this->plannedAmount;
		$payment['commission'] = $this->commission;
		$payment['actualDate'] = $this->actualDate;
		$payment['actualAmount'] = $this->actualAmount;
		$payment['stockPrice'] = $this->stockPrice;
		$payment['optionMemo'] = $this->optionMemo;

		return $payment;
	}
}

/**
 * 支払予実情報リストを取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no プロジェクト属性番号
 * @param mixed $type 支払タイプ
 * @throws Exception
 * @return ArrayObject
 */
function getPayment(PDO $pdo, $tNo) {

	$sql =
		'SELECT '.
			'* '.
		'FROM '.
			'PAYMENT_MANAGEMENT '.
		'WHERE '.
			'TRANSACTION_NO = :trans_no '.
		'ORDER BY '.
			'PLANNED_PAYMENT_DATE';

	$info = new payment();
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':trans_no', $tNo, PDO::PARAM_INT);
		$stmt->execute();

		while ($row = $stmt->fetch()) {

			$info->setAttrNo($row['PROJ_ATTR_NO']);
			$info->setTransNo($row['TRANSACTION_NO']);
			$info->setType($row['PAYMENT_TYPE']);
			$info->setTermFrom($row['TERM_FROM']);
			$info->setTermTo($row['TERM_TO']);
			$info->setPlannedDate($row['PLANNED_PAYMENT_DATE']);
			$info->setPlannedAmount($row['PLANNED_PAY_AMOUNT']);
			$info->setCommission($row['COMMISION']);
			$info->setActualDate($row['ACTUAL_PAYMENT_DATE']);
			$info->setActualAmount($row['ACTUAL_PAY_AMOUNT']);
			$info->setStockPrice($row['STOCK_PRICE']);
			$info->setOptionMemo($row['OPTION_MEMO']);
			break;
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $info->toArray();
}

/**
 * 支払予実情報リストを取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no プロジェクト属性番号
 * @param mixed $type 支払タイプ
 * @throws Exception
 * @return ArrayObject
 */
function getPaymentList(PDO $pdo, $no, $type) {

	$sql = 'SELECT * FROM PAYMENT_MANAGEMENT WHERE PROJ_ATTR_NO = :proj_attr_no AND PAYMENT_TYPE = :type ORDER BY PLANNED_PAYMENT_DATE';

	$list = array();
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':proj_attr_no', $no, PDO::PARAM_INT);
		$stmt->bindParam(':type', $type, PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {

			$info = new payment();
			$info->setAttrNo($row['PROJ_ATTR_NO']);
			$info->setTransNo($row['TRANSACTION_NO']);
			$info->setType($row['PAYMENT_TYPE']);
			$info->setTermFrom($row['TERM_FROM']);
			$info->setTermTo($row['TERM_TO']);
			$info->setPlannedDate($row['PLANNED_PAYMENT_DATE']);
			$info->setPlannedAmount($row['PLANNED_PAY_AMOUNT']);
			$info->setCommission($row['COMMISION']);
			$info->setActualDate($row['ACTUAL_PAYMENT_DATE']);
			$info->setActualAmount($row['ACTUAL_PAY_AMOUNT']);
			$info->setStockPrice($row['STOCK_PRICE']);
			$info->setOptionMemo($row['OPTION_MEMO']);

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
 * 投資家をキーに支払予実を取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no 投資家No
 * @param mixed $yyyyMM 年月
 * @throws Exception
 * @return array
 */
function getPaymentListByInvestor(PDO $pdo, $no, $yyyyMM) {

	$sql =
		'SELECT '.
			'tab1.TRANSACTION_NO, '.
			'tab4.ITEM_NAME, '.
			'tab3.PROJECT_NAME, '.
			'tab1.PAYMENT_TYPE, './/
			'tab1.PLANNED_PAYMENT_DATE, '.
			'tab1.ACTUAL_PAYMENT_DATE, '.
			'tab1.PLANNED_PAY_AMOUNT, '.
			'tab1.ACTUAL_PAY_AMOUNT '.
		'FROM '.
			'PAYMENT_MANAGEMENT tab1, '.
			'PROJECT_ATTRIBUTE tab2, '.
			'INVESTMENT_PROJECT tab3, '.
			'CODE_MASTER tab4 '.
		'WHERE '.
				'tab1.PROJ_ATTR_NO = tab2.PROJ_ATTR_NO '.
			'AND tab2.PROJECT_NO = tab3.PROJECT_NO '.
			'AND tab2.INVESTOR_NO = :investor_no '.
			'AND tab1.PLANNED_PAYMENT_DATE >= :firstdate '.
			'AND tab1.PLANNED_PAYMENT_DATE <= :lastdate '.
			'AND tab1.PAYMENT_TYPE in (\'01\', \'02\', \'03\') '.
			'AND tab4.MASTER_CODE = \'03\' '.
			'AND tab3.PROPOSAL_TYPE = tab4.ITEM_CODE ';

	$firstDate = date('Y-m-d', strtotime('first day of ' . $yyyyMM));
	$lastDate = date('Y-m-d', strtotime('last day of ' . $yyyyMM));

	$list = array();
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':investor_no', $no, PDO::PARAM_STR);
		$stmt->bindParam(':firstdate', $firstDate, PDO::PARAM_STR);
		$stmt->bindParam(':lastdate', $lastDate, PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			$payment = array();
			$payment['transNo'] = $row['TRANSACTION_NO'];
			$payment['projectName'] = '('.$row['ITEM_NAME'].') '.$row['PROJECT_NAME'];
			$payment['paymentType'] = $row['PAYMENT_TYPE'];//
			$payment['plannedDate'] = $row['PLANNED_PAYMENT_DATE'];
			$payment['plannedAmount'] = $row['PLANNED_PAY_AMOUNT'];
			$payment['actualDate'] = $row['ACTUAL_PAYMENT_DATE'];
			$payment['actualAmount'] = $row['ACTUAL_PAY_AMOUNT'];
			array_push($list, $payment);
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $list;
}

/**
 * 投資案件をキーに支払予実を取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no 投資案件No
 * @param mixed $yyyyMM 年月
 * @throws Exception
 * @return array
 */
function getPaymentListByProject(PDO $pdo, $no, $yyyyMM) {

	// 検索
	$sql =
		'SELECT '.
			'tab1.TRANSACTION_NO, '.
			'tab3.LAST_NAME, '.
			'tab3.FIRST_NAME, '.
			'tab1.PAYMENT_TYPE, './/
			'tab1.PLANNED_PAYMENT_DATE, '.
			'tab1.ACTUAL_PAYMENT_DATE, '.
			'tab1.PLANNED_PAY_AMOUNT, '.
			'tab1.ACTUAL_PAY_AMOUNT '.
		'FROM '.
			'PAYMENT_MANAGEMENT tab1, '.
			'PROJECT_ATTRIBUTE tab2, '.
			'INVESTOR tab3 '.
		'WHERE '.
				'tab1.PROJ_ATTR_NO = tab2.PROJ_ATTR_NO '.
			'AND tab2.PROJECT_NO = :project_no '.
			'AND tab2.INVESTOR_NO = tab3.INVESTOR_NO '.
			'AND tab1.PLANNED_PAYMENT_DATE >= :firstdate '.
			'AND tab1.PLANNED_PAYMENT_DATE <= :lastdate ';

	$firstDate = date('Y-m-d', strtotime('first day of ' . $yyyyMM));
	$lastDate = date('Y-m-d', strtotime('last day of ' . $yyyyMM));

	$list = array();
	try {

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':project_no', $no, PDO::PARAM_STR);
		$stmt->bindParam(':firstdate', $firstDate, PDO::PARAM_STR);
		$stmt->bindParam(':lastdate', $lastDate, PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			$payment = array();
			$payment['transNo'] = $row['TRANSACTION_NO'];
			$payment['investorName'] = $row['LAST_NAME'].$row['FIRST_NAME'];
			$payment['paymentType'] = $row['PAYMENT_TYPE'];//
			$payment['plannedDate'] = $row['PLANNED_PAYMENT_DATE'];
			$payment['plannedAmount'] = $row['PLANNED_PAY_AMOUNT'];
			$payment['actualDate'] = $row['ACTUAL_PAYMENT_DATE'];
			$payment['actualAmount'] = $row['ACTUAL_PAY_AMOUNT'];
			array_push($list, $payment);
		}

	} catch (Exception $e) {
		throw  $e;
	} finally {
		$stmt = null;
	}

	return $list;
}

/**
 * 投資案件をキーに支払予実を取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $pNo 投資案件No
 * @param mixed $iNo 投資家No
 * @param mixed $yyyyMMdd 配当日
 * @throws Exception
 * @return array
 */
function getPaymentListForUptdate(PDO $pdo, $pNo, $iNo, $yyyyMMdd) {

	// 検索
	$sql =
		'SELECT '.
			'tab1.TRANSACTION_NO, '.
			'tab3.INVESTOR_NO, '.
			'tab3.LAST_NAME, '.
			'tab3.FIRST_NAME, '.
			'tab1.PLANNED_PAYMENT_DATE, '.
			'tab1.ACTUAL_PAYMENT_DATE, '.
			'tab1.COMMISION, '.
			'tab1.PLANNED_PAY_AMOUNT, '.
			'tab1.ACTUAL_PAY_AMOUNT '.
		'FROM '.
			'PAYMENT_MANAGEMENT tab1, '.
			'PROJECT_ATTRIBUTE tab2, '.
			'INVESTOR tab3 '.
		'WHERE '.
				'tab1.PROJ_ATTR_NO = tab2.PROJ_ATTR_NO '.
			'AND tab2.INVESTOR_NO = tab3.INVESTOR_NO '.
			'AND tab2.PROJECT_NO = :project_no '.
			'AND tab1.PAYMENT_TYPE = \'01\' '.
//			'AND tab2.INVESTOR_NO = :investor_no '.
//			'AND tab1.PLANNED_PAYMENT_DATE = :haito_date '
		'ORDER BY '.
			'INVESTOR_NO ASC, PLANNED_PAYMENT_DATE ASC'
					;

//	$firstDate = date('Y-m-d', strtotime('first day of ' . $yyyyMM));
//	$lastDate = date('Y-m-d', strtotime('last day of ' . $yyyyMM));

	$list = array();
	try {

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':project_no', $pNo, PDO::PARAM_STR);
//		$stmt->bindParam(':investor_no', $iNo, PDO::PARAM_STR);
//		$stmt->bindParam(':haito_date', $yyyyMMdd, PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			$payment = array();
			$payment['transNo'] = $row['TRANSACTION_NO'];
			$payment['investorName'] = $row['LAST_NAME'].$row['FIRST_NAME'];
			$payment['plannedDate'] = $row['PLANNED_PAYMENT_DATE'];
			array_push($list, $payment);
		}

	} catch (Exception $e) {
		throw  $e;
	} finally {
		$stmt = null;
	}

	return $list;
}

/**
 * 支払予実を登録する。
 *
 * @param PDO $pdo DBコネクション
 * @param payment $info 支払予実
 * @throws Exception
 */
function insertPayment(PDO $pdo, payment $info) {

	$sql =
		'INSERT INTO PAYMENT_MANAGEMENT(' .
			'PROJ_ATTR_NO, ' .			// プロジェクト番号
			'PAYMENT_TYPE, ' .			// 支払タイプ
			'TERM_FROM, '		.		// 支払予定日
			'TERM_TO, ' .				// 支払タイプ
			'PLANNED_PAYMENT_DATE, ' .	// 支払予定日
			'COMMISION, ' .				// 手数料
			'STOCK_PRICE, ' .			// 
			'OPTION_MEMO, ' .			// 
			'PLANNED_PAY_AMOUNT ' .		// 計画支払額
		') VALUES (' .
			':proj_attr_no, '.
			':type, '.
			':term_from, '.
			':term_to, '.
			':dividend_date, ' .
			':dividend_commission, ' .
			':stock_price, ' .			// 
			':option_memo, ' .			// 
			':thanks1 ' .
		')';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':proj_attr_no', $info->getAttrNo(), PDO::PARAM_INT);
		$stmt->bindParam(':type', $info->getType(), PDO::PARAM_STR);
		$stmt->bindParam(':term_from', $info->getTermFrom(), PDO::PARAM_STR);
		$stmt->bindParam(':term_to', $info->getTermTo(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_date', $info->getPlannedDate(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_commission', $info->getCommission(), PDO::PARAM_STR);
		$stmt->bindParam(':stock_price', $info->getStockPrice(), PDO::PARAM_INT);
		$stmt->bindParam(':option_memo', $info->getOptionMemo(), PDO::PARAM_STR);
		$stmt->bindParam(':thanks1', $info->getPlannedAmount(), PDO::PARAM_INT);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}
}

/**
 * 支払予実を更新する。
 *
 * @param PDO $pdo DBコネクション
 * @param payment $info 支払予実
 * @throws Exception
 */
function updatePayment(PDO $pdo, payment $info) {

	$sql =
		'UPDATE '.
			'PAYMENT_MANAGEMENT ' .
		'SET ' .
			'TERM_FROM = :term_from, ' .				// 支払予定日
			'TERM_TO = :term_to, ' .					// 手数料
			'PLANNED_PAYMENT_DATE = :dividend_date, ' .	// 支払予定日
			'COMMISION = :dividend_commission, ' .		// 手数料
			'STOCK_PRICE = :stock_price, ' .			// 
			'OPTION_MEMO = :option_memo, ' .			// 
			'PLANNED_PAY_AMOUNT = :thanks1 ' .			// 計画支払額
		'WHERE ' .
			'TRANSACTION_NO = :trans_no';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':term_from', $info->getTermFrom(), PDO::PARAM_STR);
		$stmt->bindParam(':term_to', $info->getTermTo(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_date', $info->getPlannedDate(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_commission', $info->getCommission(), PDO::PARAM_STR);
		$stmt->bindParam(':stock_price', $info->getStockPrice(), PDO::PARAM_INT);
		$stmt->bindParam(':option_memo', $info->getOptionMemo(), PDO::PARAM_STR);
		$stmt->bindParam(':thanks1', $info->getPlannedAmount(), PDO::PARAM_INT);
		$stmt->bindParam(':trans_no', $info->getTransNo(), PDO::PARAM_INT);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * 実際支払日・実際支払額を登録する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $noトランザクション番号
 * @param mixed $date 実際支払日
 * @param mixed $amount 実際支払額
 * @throws Exception
 */
function updateActualPayment(PDO $pdo, $no, $date, $amount) {

	$sql =
		'UPDATE '.
			'PAYMENT_MANAGEMENT '.
		'SET '.
			'ACTUAL_PAYMENT_DATE = :date, '.
			'ACTUAL_PAY_AMOUNT = :amount '.
		'WHERE '.
			'TRANSACTION_NO = :transId';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':date', $date, PDO::PARAM_STR);
		$stmt->bindParam(':amount', $amount, PDO::PARAM_INT);
		$stmt->bindParam(':transId', $no, PDO::PARAM_INT);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * 配当を更新する。
 *
 * @param PDO $pdo DBコネクション
 * @param payment $info 支払予実
 * @throws Exception
 */
function updateHaito(PDO $pdo, payment $info) {

	$sql =
		'UPDATE '.
			'PAYMENT_MANAGEMENT ' .
		'SET ' .
			'COMMISION = :dividend_commission, ' .		// 配当率
			'PLANNED_PAY_AMOUNT = :thanks1 ' .			// 計画支払額
		'WHERE ' .
			'TRANSACTION_NO = :trans_no';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':dividend_commission', $info->getCommission(), PDO::PARAM_STR);
		$stmt->bindParam(':thanks1', $info->getPlannedAmount(), PDO::PARAM_INT);
		$stmt->bindParam(':trans_no', $info->getTransNo(), PDO::PARAM_INT);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * 支払予実を削除する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no トランザクション番号
 * @throws Exception
 */
function deletePayment(PDO $pdo, $no) {

	$sql =
		'DELETE FROM '.
			'PAYMENT_MANAGEMENT '.
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
