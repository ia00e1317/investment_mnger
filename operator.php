<?php

class operator {

	private $id = '';
	private $lastName = '';
	private $firstName = '';
	private $password = '';
	private $passConfirm = '';
	private $shortName = '';
	private $mailAddress = '';
	private $telHome = '';
	private $telMobile = '';

	/**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

	/**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

	/**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

	/**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

	/**
     * @return string
     */
    public function getPassConfirm()
    {
        return $this->passConfirm;
    }

	/**
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

	/**
     * @return string
     */
    public function getMailAddress()
    {
        return $this->mailAddress;
    }

	/**
     * @return string
     */
    public function getTelHome()
    {
        return $this->telHome;
    }

	/**
     * @return string
     */
    public function getTelMobile()
    {
        return $this->telMobile;
    }

	/**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

	/**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

	/**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

	/**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

	/**
     * @param string $passConfirm
     */
    public function setPassConfirm($passConfirm)
    {
        $this->passConfirm = $passConfirm;
    }

	/**
     * @param string $shortName
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;
    }

	/**
     * @param string $mailAddress
     */
    public function setMailAddress($mailAddress)
    {
        $this->mailAddress = $mailAddress;
    }

	/**
     * @param string $telHome
     */
    public function setTelHome($telHome)
    {
        $this->telHome = $telHome;
    }

	/**
     * @param string $telMobile
     */
    public function setTelMobile($telMobile)
    {
        $this->telMobile = $telMobile;
    }

}

/**
 * 指定のオペレータ情報を取得する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $no オペレータNo
 * @throws Exception
 * @return operator
 */
function getOperatorInfo(PDO $pdo, $no) {

	$sql = 'SELECT * FROM OPERATOR WHERE OPERATOR_ID = :operator_id';

	$info = new operator();
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':operator_id', $no, PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			$info->setId($row['OPERATOR_ID']);
			$info->setLastName($row['LAST_NAME']);
			$info->setFirstName($row['FIRST_NAME']);
			$info->setShortName($row['SHORT_NAME']);
// 			$info->setPassword($row['PASSWORD']);
			$info->setMailAddress($row['MAIL_ADDRESS']);
			$info->setTelHome($row['TEL_HOME']);
			$info->setTelMobile($row['TEL_MOBILE']);
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
 * オペレータリストを取得する。
 *
 * @param PDO $pdo DBコネクション
 * @throws Exception
 * @return array|string[]
 */
function getOperatorList(PDO $pdo) {

	// オペレータ一覧を取得
	$sql =
		'SELECT '.
			'OPERATOR_ID, '.
			'LAST_NAME, '.
			'FIRST_NAME '.
		'FROM '.
			'OPERATOR '.
		'WHERE '.
				'OPERATOR_TYPE != \'01\' '.
			'AND OPERATOR_ID != :myID';

	$list = array();
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':myID', $_SESSION['loginUserID'], PDO::PARAM_STR);
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			$list += array($row['OPERATOR_ID']=>$row['LAST_NAME'].' '.$row['FIRST_NAME']);
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $list;
}

/**
 * 指定のオペレータIDが有効か否かを確認する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $id オペレータID
 * @throws Exception
 * @return boolean
 */
function isValidID(PDO $pdo, $id) {

	$sql = 'SELECT COUNT(OPERATOR_ID) as COUNT FROM OPERATOR WHERE OPERATOR_ID = :id';

	try {

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':id', $id, PDO::PARAM_STR);
		$stmt->execute();

		$count = 0;
		while ($row = $stmt->fetch()) {
			$count = $row['COUNT'];
			break;
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $count < 1 ? true : false;
}

/**
 * オペレータ情報を登録する。
 *
 * @param PDO $pdo DBコネクション
 * @param operator $info オペレータ情報
 * @throws Exception
 */
function insertOperator(PDO $pdo, operator $info) {

	$sql =
		'INSERT INTO OPERATOR(' .
			'OPERATOR_ID,' .			// オペレータID
			'LAST_NAME, ' .				// オペレータ姓
			'FIRST_NAME, ' .			// オペレータ名
			'SHORT_NAME, ' .			//オペレータコード
			'PASSWORD, ' .				// パスワード
			'MAIL_ADDRESS, ' .			// メールアドレス
			'TEL_HOME, ' .				// 電話(自宅)
			'TEL_MOBILE ' .				// 電話(携帯)
		') VALUES (' .
			':operator_id, ' .
			':last_name, ' .
			':first_name, ' .
			':operator_code, ' .
			':operator_pass, ' .
			':operator_mail_address, ' .
			':operator_phone, ' .
			':operator_mobilephone ' .
		')';

	try {

		$stmt = $pdo->prepare ( $sql );

		$stmt->bindParam(':operator_id', $_POST ['operator_id'], PDO::PARAM_STR);
		$stmt->bindParam(':last_name', $_POST ['last_name'], PDO::PARAM_STR);
		$stmt->bindParam(':first_name', $_POST ['first_name'], PDO::PARAM_STR);
		$stmt->bindParam(':operator_code', $_POST ['operator_code'], PDO::PARAM_STR);
		$stmt->bindParam(':operator_pass', password_hash($_POST ['operator_pass'], PASSWORD_BCRYPT), PDO::PARAM_STR);
		$stmt->bindParam(':operator_mail_address', $_POST ['operator_mail_address'], PDO::PARAM_STR);
		$stmt->bindParam(':operator_phone', $_POST ['operator_phone'], PDO::PARAM_STR);
		$stmt->bindParam(':operator_mobilephone', $_POST ['operator_mobilephone'], PDO::PARAM_STR);

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}


/**
 * オペレータ情報を更新する。
 *
 * @param PDO $pdo DBコネクション
 * @param operator $info オペレータ情報
 * @throws Exception
 */
function updateOperator(PDO $pdo, operator $info) {

	if (empty($info->getPassword())) {
		$sql =
			'UPDATE '.
				'OPERATOR '.
			'SET '.
				'LAST_NAME = :last_name, '.
				'FIRST_NAME = :first_name, '.
				'SHORT_NAME = :operator_code, '.
				'MAIL_ADDRESS = :mail_address, '.
				'TEL_HOME = :tel_home, '.
				'TEL_MOBILE = :tel_mobile '.
			'WHERE '.
				'OPERATOR_ID = :operator_id';
	} else {
		$sql =
			'UPDATE '.
				'OPERATOR '.
			'SET '.
				'LAST_NAME = :last_name, '.
				'FIRST_NAME = :first_name, '.
				'SHORT_NAME = :operator_code, '.
				'PASSWORD = :operator_pass, '.
				'MAIL_ADDRESS = :mail_address, '.
				'TEL_HOME = :tel_home, '.
				'TEL_MOBILE = :tel_mobile '.
			'WHERE '.
				'OPERATOR_ID = :operator_id';
	}

	try {

		$stmt = $pdo->prepare($sql);

		$stmt->bindParam(':last_name', $info->getLastName(), PDO::PARAM_STR);
		$stmt->bindParam(':first_name', $info->getFirstName(), PDO::PARAM_STR);
		$stmt->bindParam(':operator_code', $info->getShortName(), PDO::PARAM_STR);
		$stmt->bindParam(':mail_address', $info->getMailAddress(), PDO::PARAM_STR);
		$stmt->bindParam(':tel_home', $info->getTelHome(), PDO::PARAM_STR);
		$stmt->bindParam(':tel_mobile', $info->getTelMobile(), PDO::PARAM_STR);
		$stmt->bindParam(':operator_id', $info->getId(), PDO::PARAM_STR);
		if (!empty($info->getPassword())) {
			$stmt->bindParam(':operator_pass', password_hash($info->getPassword(), PASSWORD_BCRYPT), PDO::PARAM_STR);
		}

		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * 指定のオペレータ情報を削除する。
 *
 * @param PDO $pdo DBコネクション
 * @param mixed $id オペレータID
 * @throws Exception
 */
function deleteOperator(PDO $pdo, $id) {

	$sql = 'DELETE FROM OPERATOR WHERE OPERATOR_ID = :operator_id';

	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':operator_id', $id, PDO::PARAM_STR);
		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * オペレーターコード一覧を取得する
 *
 * @param PDO $pdo DBコネクション
 * @throws Exception
 * @return array|String[]
 */
function getOperatorCodeList(PDO $pdo) {

	$sql = 'SELECT SHORT_NAME FROM OPERATOR';

	$list = array();
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->execute();

		while ($row = $stmt->fetch()) {
			$list[] = $row['SHORT_NAME'];
		}

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

	return $list;
}

?>
