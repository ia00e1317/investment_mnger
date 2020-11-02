<?php

class project_attr {

	private $attrNo;
	private $projectNo;
	private $projectName;
	private $investorNo;
	private $investorName;
	private $investmentAmount;
	private $startDate;
	private $endDate;
	private $contFlg;
	private $sign;
	private $attrCode;


	/**
	 * @return mixed
	 */
	public function getAttrNo()
	{
		return $this->attrNo;
	}

	/**
	 * @return mixed
	 */
	public function getProjectNo()
	{
		return $this->projectNo;
	}

	/**
	 * @return mixed
	 */
	public function getProjectName()
	{
		return $this->projectName;
	}

	/**
	 * @return mixed
	 */
	public function getInvestorNo()
	{
		return $this->investorNo;
	}

	/**
	 * @return mixed
	 */
	public function getInvestorName()
	{
		return $this->investorName;
	}

	/**
	 * @return mixed
	 */
	public function getInvestmentAmount()
	{
		return $this->investmentAmount;
	}

	/**
	 * @return mixed
	 */
	public function getStartDate()
	{
		return $this->startDate;
	}

	/**
	 * @return mixed
	 */
	public function getEndDate()
	{
		return $this->endDate;
	}

	/**
	 * @return mixed
	 */
	public function getContFlg()
	{
		return $this->contFlg;
	}

	/**
	 * @return mixed
	 */
	public function getSign()
	{
		return $this->sign;
	}

	/**
	 * @return mixed
	 */
	public function getAttrCode()
	{
		return $this->attrCode;
	}


	/**
	 * @param mixed $attrNo
	 */
	public function setAttrNo($attrNo)
	{
		$this->attrNo = $attrNo;
	}

	/**
	 * @param mixed $projectNo
	 */
	public function setProjectNo($projectNo)
	{
		$this->projectNo = $projectNo;
	}

	/**
	 * @param mixed $projectName
	 */
	public function setProjectName($projectName)
	{
		$this->projectName = $projectName;
	}

	/**
	 * @param mixed $investorNo
	 */
	public function setInvestorNo($investorNo)
	{
		$this->investorNo = $investorNo;
	}

	/**
	 * @param mixed $investorName
	 */
	public function setInvestorName($investorName)
	{
		$this->investorName = $investorName;
	}

	/**
	 * @param mixed $investmentAmount
	 */
	public function setInvestmentAmount($investmentAmount)
	{
		$this->investmentAmount = $investmentAmount;
	}

	/**
	 * @param mixed $startDate
	 */
	public function setStartDate($startDate)
	{
		$this->startDate = $startDate;
	}

	/**
	 * @param mixed $endDate
	 */
	public function setEndDate($endDate)
	{
		$this->endDate = $endDate;
	}

	/**
	 * @param mixed $contFlg
	 */
	public function setContFlg($contFlg)
	{
		$this->contFlg = $contFlg;
	}

	/**
	 * @param mixed $sign
	 */
	public function setSign($sign)
	{
		$this->sign = $sign;
	}

	/**
	 * @param mixed $attrCode
	 */
	public function setAttrCode($attrCode)
	{
		$this->attrCode = $attrCode;
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
 * プロジェクト属性リストを取得する。
 *
 * @param PDO $pdo DBコネクション　種別
 * @throws Exception
 * @return ArrayObject
 */
function getPrjAttList(PDO $pdo, $proposalType) {

	$sql =
		'SELECT '.
			'tab1.PROJ_ATTR_NO, '.
			'tab1.PROJECT_NO, '.
			'tab1.ATTR_CODE, '.
			'tab2.PROJECT_NAME, '.
			'tab2.START_DATE, '.
			'tab4.ITEM_NAME, '.
			'tab1.INVESTOR_NO, '.
			'tab3.LAST_NAME, '.
			'tab3.FIRST_NAME '.
		'FROM '.
			'PROJECT_ATTRIBUTE tab1, '.
			'INVESTMENT_PROJECT tab2, '.
			'INVESTOR tab3, '.
			'CODE_MASTER tab4 '.
		'WHERE '.
				'tab1.PROJECT_NO = tab2.PROJECT_NO '.
			'AND tab1.INVESTOR_NO = tab3.INVESTOR_NO '.
			'AND tab4.MASTER_CODE = \'03\' '.
			'AND tab2.PROPOSAL_TYPE = tab4.ITEM_CODE '.
			'AND tab2.PROPOSAL_TYPE = :proposal_type '.
		'ORDER BY '.
			'PROJECT_NAME, START_DATE, INVESTOR_NO ';

	$list = array();
	try {

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':proposal_type', $proposalType, PDO::PARAM_STR);
		$stmt->execute();

		//while ($row = $stmt->fetch()) {
		//	$str = $row['OLD_FLG']. '('.$row['ITEM_NAME'].') '. $row['PROJECT_NAME']. '　｜　'. date('Y/m/d', strtotime($row['START_DATE'])).
		//			'　｜　'. 'No.'. substr('00000'. $row['INVESTOR_NO'], -5). ': '. $row['LAST_NAME']. ' '. $row['FIRST_NAME'];
		//	$list += array($row['PROJ_ATTR_NO']=>$str);
		//}

		while ($row = $stmt->fetch()) {
			$aryRow = array(
				'projAttrNo'=>$row['PROJ_ATTR_NO'],
				'attrCode'=>$row['ATTR_CODE'],
				'projectName'=>$row['PROJECT_NAME'],
				//'investorNo'=>substr('00000'. $row['INVESTOR_NO'], -3),
				'investorNo'=>substr('000'. $row['INVESTOR_NO'], -3),
				'lastName'=>$row['LAST_NAME'],
				'firstName'=>$row['FIRST_NAME']
			);

			array_push($list, $aryRow);
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return new ArrayObject($list);
}

/**
 * プロジェクト属性情報を取得する。
 *
 * @param PDO $pdo トランザクション番号
 * @param mixed $no プロジェクト属性No
 * @throws Exception
 * @return project_attr
 */
function getProjectAttr(PDO $pdo, $no) {

	$sql =
		'SELECT '.
			'tab1.*, '.
			'tab2.PROJECT_NAME, '.
			'tab3.LAST_NAME, '.
			'tab3.FIRST_NAME, '.
			'tab4.ITEM_NAME '.
		'FROM '.
			'PROJECT_ATTRIBUTE tab1, '.
			'INVESTMENT_PROJECT tab2, '.
			'INVESTOR tab3, '.
			'CODE_MASTER tab4 '.
		'WHERE '.
				'tab1.PROJ_ATTR_NO = :proj_attr_no '.
			'AND tab1.PROJECT_NO = tab2.PROJECT_NO '.
			'AND tab1.INVESTOR_NO = tab3.INVESTOR_NO '.
			'AND tab4.MASTER_CODE = \'03\' '.
			'AND tab2.PROPOSAL_TYPE = tab4.ITEM_CODE';

	$info = new project_attr();
	try {

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':proj_attr_no', $no, PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {

			$info->setAttrNo($row['PROJ_ATTR_NO']);
			$info->setProjectNo($row['PROJECT_NO']);
			$info->setProjectName('('.$row['ITEM_NAME'].') '.$row['PROJECT_NAME']);
			$info->setInvestorNo($row['INVESTOR_NO']);
			$info->setInvestorName($row['LAST_NAME'].$row['FIRST_NAME']);
			$info->setInvestmentAmount($row['INVESTMENT_AMOUNT']);
			$info->setStartDate($row['START_DATE']);
			$info->setEndDate($row['END_DATE']);
			$info->setContFlg($row['CONTRACT_FLG']);
			$info->setSign($row['SIGN']);
			$info->setAttrCode($row['ATTR_CODE']);

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
 * プロジェクト属性情報を登録する。
*
* @param PDO $pdo DBコネクション
* @param project_attr $info プロジェクト属性情報
* @throws Exception
* @return string プロジェクト属性番号
*/
function insertProjectAttr(PDO $pdo, project_attr $info) {

	$sql =
		'INSERT INTO PROJECT_ATTRIBUTE(' .
			'PROJECT_NO, ' .			// プロジェクト番号
			'INVESTOR_NO, ' .			// 投資家番号
			'INVESTMENT_AMOUNT, ' .		// 投資金額
			'START_DATE, ' .			// 開始日
			'END_DATE, ' .				// 終了日
			'ATTR_CODE ' .				// 覚書コード
		') VALUES (' .
			':project_name, '.
			':investor_name, '.
			':investment, ' .
			':start_date, ' .
			':end_date, ' .
			':attr_code ' .
		')';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':project_name', $info->getProjectNo(), PDO::PARAM_STR);
		$stmt->bindParam(':investor_name', $info->getInvestorNo(), PDO::PARAM_STR);
		$stmt->bindParam(':investment', $info->getInvestmentAmount(), PDO::PARAM_STR);
		$stmt->bindParam(':start_date', $info->getStartDate(), PDO::PARAM_STR);
		$stmt->bindParam(':end_date', $info->getEndDate(), PDO::PARAM_STR);
		$stmt->bindParam(':attr_code', $info->getAttrCode(), PDO::PARAM_STR);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $pdo->lastInsertId();
}

/**
 * プロジェクト属性を更新する。
*
* @param PDO $pdo DBコネクション
* @param project_attr $info プロジェクト属性情報
* @throws Exception
*/
function updateProjectAttr(PDO $pdo, project_attr $info) {

	$sql =
		'UPDATE '.
			'PROJECT_ATTRIBUTE ' .
		'SET ' .
			'PROJECT_NO = :project_no, ' .			// プロジェクト番号
			'INVESTOR_NO = :investor_no, ' .		// 投資家番号
			'INVESTMENT_AMOUNT = :investment, ' .	// 投資金額
			'START_DATE = :start_date, ' .			// 投資家番号
			'END_DATE = :end_date, ' .				// 投資金額
			'ATTR_CODE = :attr_code ' .				// 覚書コード
			//'OLD_FLG = :old_flg ' .					// 旧版フラグ
		'WHERE ' .
			'PROJ_ATTR_NO = :proj_attr_no';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':project_no', $info->getProjectNo(), PDO::PARAM_INT);
		$stmt->bindParam(':investor_no', $info->getInvestorNo(), PDO::PARAM_STR);
		$stmt->bindParam(':investment', $info->getInvestmentAmount(), PDO::PARAM_STR);
		$stmt->bindParam(':start_date', $info->getStartDate(), PDO::PARAM_STR);
		$stmt->bindParam(':end_date', $info->getEndDate(), PDO::PARAM_STR);
		$stmt->bindParam(':proj_attr_no', $info->getAttrNo(), PDO::PARAM_INT);
		$stmt->bindParam(':attr_code', $info->getAttrCode(), PDO::PARAM_STR);
		//$stmt->bindParam(':old_flg', $info->getOldFlg(), PDO::PARAM_INT);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * 覚書コードを作成する
 *
 * @param PDO $pdo DBコネクション
 * @param project_attr $info 覚書属性情報
 * @param mixed $loginUserID ユーザーID
 * @throws Exception
 * @return string
 */
function makeAttrCode(PDO $pdo, project_attr $info) {

	$projInfo = getProjectInfo($pdo, $info->getProjectNo());
	$attrCode = "";

	$sqlOperator =
		'SELECT SHORT_NAME FROM OPERATOR WHERE OPERATOR_ID = :login_user_id';
	$sqlCategory =
		'SELECT CAT_NAME FROM CATEGORY_MANAGER WHERE CATEGORY_ID = :category_id';

	try {

		$stmtOperator = $pdo->prepare($sqlOperator);
		$stmtOperator->bindParam(':login_user_id', $_SESSION['loginUserID'], PDO::PARAM_STR);
		$stmtOperator->execute();

		$stmtCategory = $pdo->prepare($sqlCategory);
		$stmtCategory->bindParam(':category_id', $projInfo->getCategoryNumber(), PDO::PARAM_INT);
		$stmtCategory->execute();

		while ($row = $stmtOperator->fetch()) {
			$shortName = $row['SHORT_NAME'];
			break;
		}
		while ($row = $stmtCategory->fetch()) {
			$catName = $row['CAT_NAME'];
			break;
		}

		$attrCode = "No.";
		$attrCode .= date('Ymd', strtotime($projInfo->getStartDate()));
		$attrCode .= "-";
		$attrCode .= $shortName;
		$attrCode .= "-";
		//$attrCode .= mb_substr($projInfo->getCategoryName(), 0, 1);			
		$attrCode .= $catName;
		$attrCode .= "-";
		$attrCode .= str_pad($info->getInvestorNo(), 3, 0, STR_PAD_LEFT);

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $attrCode;

}

/**
* 締結処理を行う。
* (締結フラグ、サインを登録する)
*
* @param PDO $pdo DBコネクション
* @param mixed $no プロジェクト属性No
* @param mixed $sign サイン
* @throws Exception
*/
function conclusion(PDO $pdo, $no, $sign) {

	// プロジェクト属性更新
	$sql =
		'UPDATE '.
			'PROJECT_ATTRIBUTE ' .
		'SET ' .
			'CONTRACT_FLG = \'1\', ' .	// 投資家番号
			'SIGN = :sign ' .			// 投資金額
		'WHERE ' .
			'PROJ_ATTR_NO = :proj_attr_no';

	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':sign', $sign, PDO::PARAM_LOB);
		$stmt->bindParam(':proj_attr_no', $no, PDO::PARAM_INT);
		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * ファンド情報を取得する
 *
 */
function getFundInfo(PDO $pdo) {

	$sql = 'SELECT ITEM_NAME FROM CODE_MASTER WHERE MASTER_CODE = \'04\' ';

	$list = array();
	try {

		$stmt = $pdo->prepare($sql);
		$stmt->execute();

		$list['fundMgrName'] = $stmt->fetch()['ITEM_NAME'];//資金管理者 名前
		$list['fundMgrAdd'] = $stmt->fetch()['ITEM_NAME'];//資金管理者 住所

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}
	return $list;
}

?>
