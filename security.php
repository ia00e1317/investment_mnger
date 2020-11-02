<?php

/**
 * CSRFtokenを設定する。
 *
 * @return string CSRFtoken
 */
function setCSRFtoken() {

	$csrf_token = bin2hex(openssl_random_pseudo_bytes(16));
	$_SESSION['csrf_token'] = $csrf_token;

	return $csrf_token;
}

/**
 * CSRF対策チェックを行う。
 *
 * @return boolean true:正常、false:異常
 */
function checkCSRFtoken() {

	$result = false;
	if (isset($_POST["csrf_token"])
			&& $_POST["csrf_token"] === $_SESSION['csrf_token']) {
		$result = true;
	}

	return $result;
}

/**
 * サニタイジングを行う。
 *
 * @param mixed $str
 * @return string
 */
function h($str) {
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

?>