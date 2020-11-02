<?php

function after($from, $to){

	return strtotime($from) < strtotime($to);
}

?>