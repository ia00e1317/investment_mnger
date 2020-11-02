<?php

function calcWithoutTax($investment, $fee) {

	if ($investment * $fee /100 < 1) {
		$value = 0;
	} else {
		//$value = ceil($investment *$fee / 108);
		$value = ceil($investment *$fee / 110);
	}

	return $value;
}

?>