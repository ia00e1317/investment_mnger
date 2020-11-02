<?php

class category_signup {

	//プロパティ
	private $categoryId = '';
	private $categoryName = '';
	private $catName = '';
	private $categoryFlg = '';

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->categoryId;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->categoryName;
	}

	/**
	 * @return mixed
	 */
	public function getCatName()
	{
		return $this->catName;
	}

	/**
	 * @return mixed
	 */
	public function getFlg()
	{
		return $this->categoryFlg;
	}

	

	/**
	 * @param mixed $categoryId
	 */
	public function setId($categoryId)
	{
		$this->categoryId = $categoryId;
	}

	/**
	 * @param mixed $categoryName
	 */
	public function setName($categoryName)
	{
		$this->categoryName = $categoryName;
	}

	/**
	 * @param mixed $catName
	 */
	public function setCatName($catName)
	{
		$this->catName = $catName;
	}

	/**
	 * @param mixed $categoryFlg
	 */
	public function setFlg($categoryFlg)
	{
		$this->categoryFlg = $categoryFlg;
	}


	public function toArray() {

		$category = array();
		$category['categoryId'] = $this->categoryId;
		$category['categoryName'] = $this->categoryName;
		$category['catName'] = $this->catName;
		$category['categoryFlg'] = $this->categoryFlg;

		return $category;
	}	

}


/**
 * 登録済みのカテゴリーリストを取得する。
 *
 * @param PDO $pdo DBコネクション
 * @throws Exception
 * @return ArrayObject
 */
function getCategoryDefList(PDO $pdo) {

	$sql =
		'SELECT '.
			'* '.
		'FROM '.
			'CATEGORY_MANAGER '.
		'WHERE '.
				'CATEGORY_FLG = 0 '.
		'ORDER BY '.
			'CATEGORY_ID DESC';

	$list = array();
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->execute();

		while ($row = $stmt->fetch()) {

			$info = new category_signup();
			$info->setId($row['CATEGORY_ID']);
			$info->setName($row['CATEGORY_NAME']);
			$info->setCatName($row['CAT_NAME']);
			$info->setFlg($row['CATEGORY_FLG']);

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
 * 登録
 *
 * @param PDO $pdo DBコネクション
 * @param category_signup $info 
 */
function insertCategory(PDO $pdo, category_signup $info) {

	$sql =
		'INSERT INTO CATEGORY_MANAGER('.
			'CATEGORY_NAME, ' .				// カテゴリー名
			'CAT_NAME, ' .					// カテゴリー省略名
			'CATEGORY_FLG ' .				// カテゴリー削除フラグ
		') VALUES (' .
			':category_name, ' .
			':cat_name, ' .
			'0 ' .
		')';

	try {

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':category_name', $info->getName(), PDO::PARAM_STR);
		$stmt->bindParam(':cat_name', $info->getCatName(), PDO::PARAM_STR);
		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

/**
 * カテゴリー情報を非表示にする。
 *
 * @param PDO $pdo DBコネクション
 * @param $id カテゴリーID
 * @param $flg フラグ
 * @throws Exception
 */
function hideCategory(PDO $pdo, $id, $flg) {

	// 削除フラグ設定
	$sql = 'UPDATE CATEGORY_MANAGER SET CATEGORY_FLG = :category_flg WHERE CATEGORY_ID = :category_id';

	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':category_flg', $flg, PDO::PARAM_INT );
		$stmt->bindParam(':category_id', $id, PDO::PARAM_INT );
		$stmt->execute();

	} catch (Exception $e) {
		throw $e;
	} finally {
		$stmt = null;
	}

}

?>