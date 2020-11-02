
/**
 * カンマ編集
 */
function updateTextView(_obj, _hid_obj){

	var num = getNumber(_obj.val());
	var numlen = num.length;
	
	//if(num == 0 && numlen >19){
	if(num == 0 && numlen >16){
		_obj.val('');
		_hid_obj.val('');
	} 
	else {
		_obj.val(num.toLocaleString());
		_hid_obj.val(num);
	}
}
		
	

/**
 * 数値変換
 */
function getNumber(_str){

	// カンマ削除
	_str = _str.split(',').join('');

	var arr = _str.split('');
	var out = new Array();
	for(var cnt=0; cnt<arr.length; cnt++){

		if (isNaN(arr[cnt]) == false){
			out.push(arr[cnt]);
		}
	}
	return Number(out.join(''));
}

/**
 * 税抜き計算(カンマ編集済み)
 */
function calcWithoutTax(investment, fee) {

	var value;
	if (investment * fee / 100 < 1) {
		value = 0;
	} else {
		//value = Math.ceil(investment * fee / 108);
		value = Math.ceil(investment * fee / 110);
	}

	return value.toLocaleString();
}
