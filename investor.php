<?php

require_once 'DSN.php';

/**
 * 投資家Bean
 */
class investor {

	//プロパティ
	private $no = '';
	private $type = '01';
	private $lastName = '';
	private $firstName = '';
	private $lastNameKana = '';
	private $firstNameKana = '';
	private $mailAddress = '';
	private $postCode = '';
	private $address = '';
	private $tel = '';
	private $introducedInvestor = '';
	private $introducedInvestorName = '';
	private $bankName = '';
	private $branchNo = '';
	private $accountType = '普通';
	private $accountNo = '';
	private $accountName = '';
	private $registrationFee = '';
	private $introducerDistribution = '';
	private $delFlg = '';

	/**
     * @return mixed
     */
    public function getNo()
    {
        return $this->no;
    }

	/**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

	/**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

	/**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

	/**
     * @return mixed
     */
    public function getLastNameKana()
    {
        return $this->lastNameKana;
    }

	/**
     * @return mixed
     */
    public function getFirstNameKana()
    {
        return $this->firstNameKana;
    }

	/**
     * @return mixed
     */
    public function getMailAddress()
    {
        return $this->mailAddress;
    }

	/**
     * @return mixed
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

	/**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

	/**
     * @return mixed
     */
    public function getTel()
    {
        return $this->tel;
    }

	/**
     * @return mixed
     */
    public function getIntroducedInvestor()
    {
        return $this->introducedInvestor;
    }

	/**
     * @return mixed
     */
    public function getIntroducedInvestorName()
    {
        return $this->introducedInvestorName;
    }

	/**
     * @return mixed
     */
    public function getBankName()
    {
        return $this->bankName;
    }

	/**
     * @return mixed
     */
    public function getBranchNo()
    {
        return $this->branchNo;
    }

	/**
     * @return mixed
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

	/**
     * @return mixed
     */
    public function getAccountNo()
    {
        return $this->accountNo;
    }

	/**
     * @return mixed
     */
    public function getAccountName()
    {
        return $this->accountName;
    }

	/**
     * @return mixed
     */
    public function getRegistrationFee()
    {
        return $this->registrationFee;
    }

	/**
     * @return mixed
     */
    public function getIntroducerDistribution()
    {
        return $this->introducerDistribution;
    }

	/**
     * @return mixed
     */
    public function getDelFlg()
    {
        return $this->delFlg;
    }

	/**
     * @param mixed $no
     */
    public function setNo($no)
    {
        $this->no = $no;
    }

	/**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

	/**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

	/**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

	/**
     * @param mixed $lastNameKana
     */
    public function setLastNameKana($lastNameKana)
    {
        $this->lastNameKana = $lastNameKana;
    }

	/**
     * @param mixed $firstNameKana
     */
    public function setFirstNameKana($firstNameKana)
    {
        $this->firstNameKana = $firstNameKana;
    }

	/**
     * @param mixed $mailAddress
     */
    public function setMailAddress($mailAddress)
    {
        $this->mailAddress = $mailAddress;
    }

	/**
     * @param mixed $postCode
     */
    public function setPostCode($postCode)
    {
        $this->postCode = $postCode;
    }

	/**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

	/**
     * @param mixed $tel
     */
    public function setTel($tel)
    {
        $this->tel = $tel;
    }

	/**
     * @param mixed $introducedInvestor
     */
    public function setIntroducedInvestor($introducedInvestor)
    {
        $this->introducedInvestor = $introducedInvestor;
    }

	/**
     * @param mixed $introducedInvestorName
     */
    public function setIntroducedInvestorName($introducedInvestorName)
    {
        $this->introducedInvestorName = $introducedInvestorName;
    }

	/**
     * @param mixed $bankName
     */
    public function setBankName($bankName)
    {
        $this->bankName = $bankName;
    }

	/**
     * @param mixed $branchNo
     */
    public function setBranchNo($branchNo)
    {
        $this->branchNo = $branchNo;
    }

	/**
     * @param mixed $accountType
     */
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;
    }

	/**
     * @param mixed $accountNo
     */
    public function setAccountNo($accountNo)
    {
        $this->accountNo = $accountNo;
    }

	/**
     * @param mixed $accountName
     */
    public function setAccountName($accountName)
    {
        $this->accountName = $accountName;
    }

	/**
     * @param mixed $registrationFee
     */
    public function setRegistrationFee($registrationFee)
    {
        $this->registrationFee = $registrationFee;
    }

	/**
     * @param mixed $introducerDistribution
     */
    public function setIntroducerDistribution($introducerDistribution)
    {
        $this->introducerDistribution = $introducerDistribution;
    }

	/**
     * @param mixed $delFlg
     */
    public function setDelFlg($delFlg)
    {
        $this->delFlg = $delFlg;
    }

}


/**
 * 指定の投資家情報を取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param string $no 投資家No
 * @throws Exception
 * @return investor
 */
function getInvestorInfo(PDO $pdo, $no) {

	$sql =
		'SELECT '.
			'tab1.INVESTOR_NO, '.
			'tab1.INVESTOR_TYPE, '.
			'tab1.LAST_NAME, '.
			'tab1.FIRST_NAME, '.
			'tab1.LAST_NAME_KANA, '.
			'tab1.FIRST_NAME_KANA, '.
			'tab1.MAIL_ADDRESS, '.
			'tab1.POSTAL_CODE, '.
			'tab1.ADDRESS, '.
			'tab1.TEL, '.
			'tab1.INTRODUCED_INVESTOR, '.
			'tab1.BANK_NAME, '.
			'tab1.BRANCH_NO, '.
			'tab1.ACCOUNT_TYPE, '.
			'tab1.ACCOUNT_NO, '.
			'tab1.ACCOUNT_NAME, '.
			'tab1.REGISTRATION_FEE, '.
			'tab1.INTRODUCER_DISTRIBUTION, '.
			'tab1.DELETE_FLG, '.
			'tab2.LAST_NAME as NAME1, '.
			'tab2.FIRST_NAME as NAME2 '.
//			'AES_DECRYPT(UNHEX(tab1.INVESTOR_INFO), \'tress\') as INVESTOR_INFO '.
		'FROM '.
			'INVESTOR as tab1 '.
				'LEFT OUTER JOIN INVESTOR as tab2 '.
					'ON tab1.INTRODUCED_INVESTOR = tab2.INVESTOR_NO '.
		'WHERE '.
			'tab1.INVESTOR_NO = :investor_no';

	$info = new investor();
	try {

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':investor_no', $no, PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {

//			$info = unserialize($row['INVESTOR_INFO']);
			$info->setNo($row['INVESTOR_NO']);
			$info->setType($row['INVESTOR_TYPE']);
			$info->setLastName($row['LAST_NAME']);
			$info->setFirstName($row['FIRST_NAME']);
			$info->setLastNameKana($row['LAST_NAME_KANA']);
			$info->setFirstNameKana($row['FIRST_NAME_KANA']);
			$info->setMailAddress($row['MAIL_ADDRESS']);
			$info->setPostCode($row['POSTAL_CODE']);
			$info->setAddress($row['ADDRESS']);
			$info->setTel($row['TEL']);
			$info->setIntroducedInvestor($row['INTRODUCED_INVESTOR']);
			$info->setIntroducedInvestorName($row['NAME1'].$row['NAME2']);
			$info->setBankName($row['BANK_NAME']);
			$info->setBranchNo($row['BRANCH_NO']);
			$info->setAccountType($row['ACCOUNT_TYPE']);
			$info->setAccountNo($row['ACCOUNT_NO']);
			$info->setAccountName($row['ACCOUNT_NAME']);
			$info->setRegistrationFee($row['REGISTRATION_FEE']);
			$info->setIntroducerDistribution($row['INTRODUCER_DISTRIBUTION']);
			$info->setDelFlg($row['DELETE_FLG']);
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
 * 登録済み投資家の一覧を取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param array|string[] $excludeNo 除外投資家No
 * @throws Exception
 * @return array|string[]
 */
function getInvestorList(PDO $pdo, array $excludeNo) {

	//$sql = 'SELECT INVESTOR_NO, lpad(INVESTOR_NO, 5, "0") as NO_STR, LAST_NAME, FIRST_NAME FROM INVESTOR WHERE DELETE_FLG = 0 ORDER BY NO_STR';
	$sql = 'SELECT INVESTOR_NO, lpad(INVESTOR_NO, 3, "0") as NO_STR, LAST_NAME, FIRST_NAME FROM INVESTOR WHERE DELETE_FLG = 0 ORDER BY NO_STR';

	$list = array();
	try {

		$stmt = $pdo->prepare($sql);
		$stmt->execute();

		while ($row = $stmt->fetch()) {

			if (in_array($row['INVESTOR_NO'], $excludeNo) ) {
				continue;
			}

			$list += array($row['INVESTOR_NO']=>'No.'.$row['NO_STR'].'　'.$row['LAST_NAME'].' '.$row['FIRST_NAME']);
		}

	} catch (Exception $e) {
		throw $e;

	} finally {
		$stmt = null;
	}

	return $list;
}

/**
 * 指定の投資家Noが有効(登録済みでない)かチェックする。
 *
 * @param PDO $pdo DBコネクション
 * @param string $no 投資家No
 * @throws Exception
 * @return boolean
 */
function isValidNo(PDO $pdo, $no) {

	$sql = 'SELECT count(*) COUNT FROM INVESTOR WHERE INVESTOR_NO = :investor_no';

	$count = 0;
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':investor_no', $no, PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			$count = $row['COUNT'];
			break;
		}

	} catch (Exception $e) {
		throw  $e;
	} finally {
		$stmt = null;
	}

	return $count < 1 ? true : false;
}

/**
 * 投資家情報を登録する。
 *
 * @param PDO $pdo DBコネクション
 * @param investor $info 投資家情報
 */
function insertInvestor(PDO $pdo, investor $info) {

	$sql =
		'INSERT INTO INVESTOR('.
			'INVESTOR_NO, ' .				// 個人No
			'INVESTOR_TYPE, ' .				// 種別
			'LAST_NAME, ' .					// 苗字
			'FIRST_NAME, ' .				// 名前
			'LAST_NAME_KANA, ' .			// 苗字カナ
			'FIRST_NAME_KANA, ' .			// 名前カナ
			'MAIL_ADDRESS, ' .				// メールアドレス
			'POSTAL_CODE, ' .				// 郵便番号
			'ADDRESS, ' .					// 住所
			'TEL, ' .						// 電話番号
			'INTRODUCED_INVESTOR, ' .		// 紹介投資家
			'BANK_NAME, ' .					// 銀行名
			'BRANCH_NO, ' .					// 支店番号
			'ACCOUNT_TYPE, ' .				// 口座種別
			'ACCOUNT_NO, ' .				// 口座番号
			'ACCOUNT_NAME, ' .				// 口座名義
			'REGISTRATION_FEE, ' .			// 登録費用
			'INTRODUCER_DISTRIBUTION ' .	// 紹介者配分割合
//			'INVESTOR_INFO '.
		') VALUES (' .
			':personal_number, ' .
			':type, ' .
			':name_1, ' .
			':name_2, ' .
			':read_1, ' .
			':read_2, ' .
			':mail_address, ' .
			':postal, ' .
			':address, ' .
			':phone, ' .
			':introducing_investor, ' .
			':bank_name, ' .
			':branch_number, ' .
			':type_of_account, ' .
			':account_number, ' .
			':account_holder, ' .
			':registration_fee, ' .
			':distribution ' .
//			'HEX(AES_ENCRYPT(:infoObj, \'tress\')) ' .
		')';

	try {

		$stmt = $pdo->prepare($sql);

		//$stmt->bindParam(':personal_number', $info->getNo(), PDO::PARAM_INT);
		$stmt->bindParam(':personal_number', $info->getNo(), PDO::PARAM_STR);
		$stmt->bindParam(':type', $info->getType(), PDO::PARAM_STR);
		$stmt->bindParam(':name_1', $info->getLastName(), PDO::PARAM_STR);
		$stmt->bindParam(':name_2', $info->getFirstName(), PDO::PARAM_STR);
		$stmt->bindParam(':read_1', $info->getLastNameKana(), PDO::PARAM_STR);
		$stmt->bindParam(':read_2', $info->getFirstNameKana(), PDO::PARAM_STR);
		$stmt->bindParam(':mail_address', $info->getMailAddress(), PDO::PARAM_STR);
		$stmt->bindParam(':postal', $info->getPostCode(), PDO::PARAM_STR);
		$stmt->bindParam(':address', $info->getAddress(), PDO::PARAM_STR);
		$stmt->bindParam(':phone', $info->getTel(), PDO::PARAM_STR);
		$stmt->bindParam(':introducing_investor', $info->getIntroducedInvestor(), PDO::PARAM_STR);
		$stmt->bindParam(':bank_name', $info->getBankName(), PDO::PARAM_STR);
		$stmt->bindParam(':branch_number', $info->getBranchNo(), PDO::PARAM_STR);
		$stmt->bindParam(':type_of_account', $info->getAccountType(), PDO::PARAM_STR);
		$stmt->bindParam(':account_number', $info->getAccountNo(), PDO::PARAM_STR);
		$stmt->bindParam(':account_holder', $info->getAccountName(), PDO::PARAM_STR);
		$stmt->bindParam(':registration_fee', $info->getRegistrationFee(), PDO::PARAM_INT);
		$stmt->bindParam(':distribution', $info->getIntroducerDistribution(), PDO::PARAM_STR);
//		$stmt->bindParam(':infoObj', serialize($info), PDO::PARAM_LOB);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * 投資家情報を更新する。
 *
 * @param PDO $pdo DBコネクション
 * @param investor $info 投資家情報
 * @throws Exception
 */
function updateInvestor(PDO $pdo, investor $info) {

	$sql =
		'UPDATE '.
			'INVESTOR '.
		'SET '.
			'INVESTOR_TYPE = :type, '.
			'LAST_NAME = :name_1, '.
			'FIRST_NAME = :name_2, '.
			'LAST_NAME_KANA = :read_1, '.
			'FIRST_NAME_KANA = :read_2, '.
			'MAIL_ADDRESS = :mail_address, '.
			'POSTAL_CODE = :postal, '.
			'ADDRESS = :address, '.
			'TEL = :phone, '.
			'INTRODUCED_INVESTOR = :introducing_investor, '.
			'BANK_NAME = :bank_name, '.
			'BRANCH_NO = :branch_number, '.
			'ACCOUNT_TYPE = :type_of_account, '.
			'ACCOUNT_NO = :account_number, '.
			'ACCOUNT_NAME = :account_holder, '.
			'REGISTRATION_FEE = :registration_fee, '.
			'INTRODUCER_DISTRIBUTION = :distribution '.
		'WHERE '.
			'INVESTOR_NO = :personal_number';

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':personal_number', $info->getNo(), PDO::PARAM_STR);
		$stmt->bindParam(':type', $info->getType(), PDO::PARAM_STR);
		$stmt->bindParam(':name_1', $info->getLastName(), PDO::PARAM_STR);
		$stmt->bindParam(':name_2', $info->getFirstName(), PDO::PARAM_STR);
		$stmt->bindParam(':read_1', $info->getLastNameKana(), PDO::PARAM_STR);
		$stmt->bindParam(':read_2', $info->getFirstNameKana(), PDO::PARAM_STR);
		$stmt->bindParam(':mail_address', $info->getMailAddress(), PDO::PARAM_STR);
		$stmt->bindParam(':postal', $info->getPostCode(), PDO::PARAM_STR);
		$stmt->bindParam(':address', $info->getAddress(), PDO::PARAM_STR);
		$stmt->bindParam(':phone', $info->getTel(), PDO::PARAM_STR);
		$stmt->bindParam(':introducing_investor', $info->getIntroducedInvestor(), PDO::PARAM_STR);
		$stmt->bindParam(':bank_name', $info->getBankName(), PDO::PARAM_STR);
		$stmt->bindParam(':branch_number', $info->getBranchNo(), PDO::PARAM_STR);
		$stmt->bindParam(':type_of_account', $info->getAccountType(), PDO::PARAM_STR);
		$stmt->bindParam(':account_number', $info->getAccountNo(), PDO::PARAM_STR);
		$stmt->bindParam(':account_holder', $info->getAccountName(), PDO::PARAM_STR);
		$stmt->bindParam(':registration_fee', $info->getRegistrationFee(), PDO::PARAM_INT);
		$stmt->bindParam(':distribution', $info->getIntroducerDistribution(), PDO::PARAM_STR);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * 投資家情報を削除する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no 投資家No
 * @throws Exception
 */
function deleteInvestor(PDO $pdo, $no) {

	// 削除フラグ設定
	$sql = 'UPDATE INVESTOR SET DELETE_FLG = 1 WHERE INVESTOR_NO = :investor_no';

	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':investor_no', $no, PDO::PARAM_STR );
		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

?>