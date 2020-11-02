<?php

class project {

	private $no = '';
	//private $type = '02';
	private $type = '01';
	private $typeName = '';

	public $categoryNumber = '';
	public $categoryName = '';
	public $memorandum = '';

	public $name = '';
	public $startDate = '';
	public $endDate = '';
	
	public $dividendMonth = '';
	public $dividendDate = '';
	public $dividendCount = '';

	public $dividendRate = '';
	public $repaymentCount = '';
	public $waitPeriod = '';



    /**
	 * @return string
	 */
	public function getNo() {
		return $this->no;
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
	public function getTypeName() {
		return $this->typeName;
	}

	/**
	 * @return string
	 */
	public function getCategoryNumber() {
		return $this->categoryNumber;
	}

	/**
	 * @return string
	 */
	public function getCategoryName() {
		return $this->categoryName;
	}

	/**
	 * @return string
	 */
	public function getMemorandum() {
		return $this->memorandum;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getStartDate() {
		return $this->startDate;
	}

	/**
	 * @return string
	 */
	public function getEndDate() {
		return $this->endDate;
	}

	/**
	 * @return string
	 */
	public function getDividendMonth() {
		return $this->dividendMonth;
	}

	/**
	 * @return string
	 */
	public function getDividendDate() {
		return $this->dividendDate;
	}

	/**
	 * @return string
	 */
	public function getDividendCount() {
		return $this->dividendCount;
	}

	/**
	 * @return string
	 */
	public function getDividendRate() {
		return $this->dividendRate;
	}

	/**
	* @return string
	*/
	public function getRepaymentCount() {
		return $this->repaymentCount;
		}

	/**
	 * @return string
	 */
	public function getWaitPeriod() {
		return $this->waitPeriod;
	}


	/**
	 * @param string $no
	 */
	public function setNo($no) {
		$this->no = $no;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @param string $typeName
	 */
	public function setTypeName($typeName) {
		$this->typeName = $typeName;
	}

	/**
	 * @param string $categoryNumber
	 */
	public function setCategoryNumber($categoryNumber) {
		$this->categoryNumber = $categoryNumber;
	}

	/**
	 * @param string $categoryName
	 */
	public function setCategoryName($categoryName) {
		$this->categoryName = $categoryName;
	}

	/**
	 * @param string $memorandum
	 */
	public function setMemorandum($memorandum) {
		$this->memorandum = $memorandum;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @param string $startDate
	 */
	public function setStartDate($startDate) {
		$this->startDate = $startDate;
	}

	/**
	 * @param string $endDate
	 */
	public function setEndDate($endDate) {
		$this->endDate = $endDate;
	}

	/**
	 * @param string $dividendMonth
	 */
	public function setDividendMonth($dividendMonth) {
		$this->dividendMonth = $dividendMonth;
	}

	/**
	 * @param string $dividendDate
	 */
	public function setDividendDate($dividendDate) {
		$this->dividendDate = $dividendDate;
	}

	/**
	 * @param string $dividendCount
	 */
	public function setDividendCount($dividendCount) {
		$this->dividendCount = $dividendCount;
	}

	/**
	 * @param string $dividendRate
	 */
	public function setDividendRate($dividendRate) {
		$this->dividendRate = $dividendRate;
	}

	/**
	 * @param string $repaymentCount
	 */
	public function setRepaymentCount($repaymentCount) {
		$this->repaymentCount = $repaymentCount;
	}

	/**
	 * @param string $waitPeriod
	 */
	public function setWaitPeriod($waitPeriod) {
		$this->waitPeriod = $waitPeriod;
	}


	public function validation() {

    	require_once 'validator.php';

    	$error = array();

    	if (!after($this->startDate, $this->endDate)) {
    		$error['endDate'] = '<p style="color:red; margin-top: 0;">終了日が開始日以前の日付になっています。</p>';
    	}

    	return $error;
    }
}

/**
 * 投資案件情報を取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no 投資案件No
 * @throws Exception
 * @return project
 */
function getProjectInfo(PDO $pdo, $no) {

	$sql =
		'SELECT '.
		'pro.PROJECT_NO, '.
		'pro.PROPOSAL_TYPE, '.
		'code.ITEM_NAME, '.
		'pro.CATEGORY_NO, '.
		'pro.CATEGORY_NAME, '.
		'pro.MEMORANDUM, '.
		'pro.PROJECT_NAME, '.
		'pro.START_DATE, '.
		'pro.END_DATE '.
		',pro.BASIC_PAYOUT_MONTH '.
		',pro.BASIC_PAYOUT_DATE '.
		',pro.DIVIDEND_COUNT '.
		',pro.BASIC_PAYOUT_RATE '.
		',pro.REPAYMENT_COUNT '.
		',pro.WAIT_PERIOD '.
	'FROM '.
		'INVESTMENT_PROJECT pro, '.
		'CODE_MASTER code '.
	'WHERE '.
			'pro.PROJECT_NO = :project_no '.
		'AND code.MASTER_CODE = \'03\' '.
		'AND pro.PROPOSAL_TYPE = code.ITEM_CODE';

	$info = new project();
	try {

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam( ':project_no', $no, PDO::PARAM_INT );
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			$info->setNo($row['PROJECT_NO']);
			$info->setType($row['PROPOSAL_TYPE']);
			$info->setTypeName($row['ITEM_NAME']);
			$info->setCategoryNumber($row['CATEGORY_NO']);
			$info->setCategoryName($row['CATEGORY_NAME']);
			$info->setMemorandum($row['MEMORANDUM']);
			$info->setName($row['PROJECT_NAME']);
			$info->setStartDate($row['START_DATE']);
			$info->setEndDate($row['END_DATE']);
			$info->setDividendMonth($row['BASIC_PAYOUT_MONTH']);
			$info->setDividendDate($row['BASIC_PAYOUT_DATE']);
			$info->setDividendCount($row['DIVIDEND_COUNT']);
			$info->setDividendRate($row['BASIC_PAYOUT_RATE']);
			$info->setRepaymentCount($row['REPAYMENT_COUNT']);
			$info->setWaitPeriod($row['WAIT_PERIOD']);

			break;
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}
	return $info;
}

/**
 * 投資案件一覧を取得する。
 *
 * @param PDO $pdo DBコネクション
 * @throws Exception
 * @return array|string[]
 */
 function getProjectList(PDO $pdo) {

 	$sql =
 		'SELECT '.
 			'pro.PROJECT_NO, '.
 			'code.ITEM_NAME, '.
			'pro.PROJECT_NAME, '.
 			'pro.START_DATE '.
 		'FROM '.
 			'INVESTMENT_PROJECT pro, '.
 			'CODE_MASTER code '.
 		'WHERE '.
 				'code.MASTER_CODE = \'03\' '.
 			'AND pro.PROPOSAL_TYPE = code.ITEM_CODE ' .
		'order by pro.PROJECT_NAME, ' .
		'pro.START_DATE DESC';
		//'order by pro.PROJECT_NAME';

 	$list = array();
 	try {
 		$stmt = $pdo->prepare($sql);
 		$stmt->execute();

 		while ($row = $stmt->fetch()) {
 			$list += array($row['PROJECT_NO']=>'('. $row['ITEM_NAME']. ') '. $row['PROJECT_NAME']. '　｜　'. date('Y/m/d', strtotime($row['START_DATE'])));
 		}

 	} catch (Exception $e) {
 		throw $e;
 	} finally {
 		$stmt = null;
 	}

 	return $list;
 }

/**
 * 投資案件を登録する。
 *
 * @param PDO $pdo DBコネクション
 * @param project $info 投資案件情報
 * @throws Exception
 * @return string プロジェクト番号
 */
function insertProject(PDO $pdo, project $info) {

	$sql =
		'INSERT INTO INVESTMENT_PROJECT(' .
			'PROPOSAL_TYPE ' .				// 投資タイプ
			',CATEGORY_NO ' .				// カテゴリナンバー
			',CATEGORY_NAME ' .				//
			',MEMORANDUM ' .				//
			',PROJECT_NAME ' .				// プロジェクト名
			',START_DATE ' .				// 開始日
			',END_DATE ' .                   // 終了日
			',BASIC_PAYOUT_MONTH ' .		// 基本配当日(月)
			',BASIC_PAYOUT_DATE ' .			// 基本配当日(日)
			',DIVIDEND_COUNT ' .			// 
			',BASIC_PAYOUT_RATE ' .			// 基本配当率
			',REPAYMENT_COUNT ' .			// 
			',WAIT_PERIOD ' .				// 待機期間
		') VALUES (' .
			':investment_type ' .
			',:category_no ' .
			',:category_name ' .
			',:memorandum ' .
			',:project_name ' .
			',:start_date ' .
			',:end_date ' .
			',:dividend_month ' .
			',:dividend_date ' .
			',:dividend_count ' .
			',:dividend_rate ' .
			',:repayment_count ' .
			',:wait_period ' .

		')';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':investment_type', $info->getType(), PDO::PARAM_STR);
		$stmt->bindParam(':category_no', $info->getCategoryNumber(), PDO::PARAM_STR);
		//$stmt->bindParam(':category_name', $info->getCategoryName(), PDO::PARAM_STR);
		$stmt->bindParam(':category_name', getCategoryLabel($pdo, $info->getCategoryNumber()), PDO::PARAM_STR);
		$stmt->bindParam(':memorandum', $info->getMemorandum(), PDO::PARAM_STR);
		$stmt->bindParam(':project_name', $info->getName(), PDO::PARAM_STR);
		$stmt->bindParam(':start_date', $info->getStartDate(), PDO::PARAM_STR);
		$stmt->bindParam(':end_date', $info->getEndDate(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_month', $info->getDividendMonth(), PDO::PARAM_INT);
		$stmt->bindParam(':dividend_date', $info->getDividendDate(), PDO::PARAM_INT);
		$stmt->bindParam(':dividend_count', $info->getDividendCount(), PDO::PARAM_INT);
		$stmt->bindParam(':dividend_rate', $info->getDividendRate(), PDO::PARAM_STR);
		$stmt->bindParam(':repayment_count', $info->getRepaymentCount(), PDO::PARAM_INT);
		$stmt->bindParam(':wait_period', $info->getWaitPeriod(), PDO::PARAM_INT);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $pdo->lastInsertId();

}

/**
 * 投資案件を更新する。
 *
 * @param PDO $pdo DBコネクション
 * @param project $info 投資案件情報
 * @throws Exception
 */
function updateProject(PDO $pdo, project $info) {

	$sql =
		'UPDATE '.
			'INVESTMENT_PROJECT '.
			'SET '.
			'PROPOSAL_TYPE = :investment_type '.
			',CATEGORY_NO = :category_no '.
			',CATEGORY_NAME = :category_name '.
			',MEMORANDUM = :memorandum '.
			',PROJECT_NAME = :project_name '.
			',START_DATE = :start_date '.
			',END_DATE = :end_date '.
			',BASIC_PAYOUT_MONTH = :dividend_month '.
			',BASIC_PAYOUT_DATE = :dividend_date '.
			',DIVIDEND_COUNT = :dividend_count '.
			',BASIC_PAYOUT_RATE = :dividend_rate '.
			',REPAYMENT_COUNT = :repayment_count '.
			',WAIT_PERIOD = :wait_period '.
		'WHERE '.
			'PROJECT_NO = :project_no';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':investment_type', $info->getType(), PDO::PARAM_STR);
		$stmt->bindParam(':category_no', $info->getCategoryNumber(), PDO::PARAM_STR);
		//$stmt->bindParam(':category_name', $info->getCategoryName(), PDO::PARAM_STR);
		$stmt->bindParam(':category_name', getCategoryLabel($pdo, $info->getCategoryNumber()), PDO::PARAM_STR);
		$stmt->bindParam(':memorandum', $info->getMemorandum(), PDO::PARAM_STR);
		$stmt->bindParam(':project_name', $info->getName(), PDO::PARAM_STR);
		$stmt->bindParam(':start_date', $info->getStartDate(), PDO::PARAM_STR);
		$stmt->bindParam(':end_date', $info->getEndDate(), PDO::PARAM_STR);
		$stmt->bindParam(':dividend_month', $info->getDividendMonth(), PDO::PARAM_INT);
		$stmt->bindParam(':dividend_date', $info->getDividendDate(), PDO::PARAM_INT);
		$stmt->bindParam(':dividend_count', $info->getDividendCount(), PDO::PARAM_INT);
		$stmt->bindParam(':dividend_rate', $info->getDividendRate(), PDO::PARAM_STR);
		$stmt->bindParam(':repayment_count', $info->getRepaymentCount(), PDO::PARAM_INT);
		$stmt->bindParam(':wait_period', $info->getWaitPeriod(), PDO::PARAM_INT);
		$stmt->bindParam(':project_no', $info->getNo(), PDO::PARAM_INT);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * カテゴリ：プルダウン選択肢を取得する
 *
 * @param PDO $pdo DBコネクション
 * @throws Exception
 * @return array
 */
function getCategoryOptionList(PDO $pdo) {

	$sql = 'SELECT CATEGORY_ID, CATEGORY_NAME FROM CATEGORY_MANAGER WHERE CATEGORY_FLG = 0 ORDER BY CATEGORY_ID';

	$list = array();
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			//$list[] = $row;
			$list += array($row['CATEGORY_ID']=>''.$row['CATEGORY_ID'].'　'.$row['CATEGORY_NAME']);
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $list;
}

/**
 * カテゴリvalue値から該当ラベルを取得する
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $id カテゴリ選択肢value値
 * @throws Exception
 * @return string
 */
function getCategoryLabel(PDO $pdo, $id) {

	$sql = 'SELECT CATEGORY_NAME FROM CATEGORY_MANAGER WHERE CATEGORY_ID = :id';

	$label = '';
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam( ':id', $id, PDO::PARAM_INT );
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			$label = $row['CATEGORY_NAME'];
			break;
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $label;
}

?>
