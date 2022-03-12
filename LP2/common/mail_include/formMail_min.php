<?php
	
ini_set('session.cookie_httponly', true);
ini_set("display_errors", "On");
date_default_timezone_set('Asia/Tokyo');
session_cache_limiter('nocache');
session_start();
session_regenerate_id(true);


//---------------------------------------------------------------------	
// メール関連  phpMailer用
//---------------------------------------------------------------------	
define('MAIL_CHARSET','iso-2022-jp');
define('MAIL_ENCODING','7bit');
define('MAIL_PHP_LANGUAGE','japanese');
define('MAIL_PHP_INTERNAL_ENCODING','UTF-8');
define('MAIL_FROM','info@s-t-s-k.com');
define('MAIL_FROM_NAME','株式会社秋冬春夏');
//define('MAIL_TO','contact@jara-network.com');
define('MAIL_TO','info@s-t-s-k.com');


//---------------------------------------------------------------------	
// フォーム形成用kit
//---------------------------------------------------------------------	
	// 都道府県
	$PREFECTURE_ARRAY = array(	''   	   =>  '都道府県',
								'北海道'   =>  '北海道',
								'青森県'   =>  '青森県',
								'岩手県'   =>  '岩手県',
								'宮城県'   =>  '宮城県',
								'秋田県'   =>  '秋田県',
								'山形県'   =>  '山形県',
								'福島県'   =>  '福島県',
								'茨城県'   =>  '茨城県',
								'栃木県'   =>  '栃木県',
								'群馬県'   =>  '群馬県',
								'埼玉県'   =>  '埼玉県',
								'千葉県'   =>  '千葉県',
								'東京都'   =>  '東京都',
								'神奈川県' =>  '神奈川県',
								'新潟県'   =>  '新潟県',
								'富山県'   =>  '富山県',
								'石川県'   =>  '石川県',
								'福井県'   =>  '福井県',
								'山梨県'   =>  '山梨県',
								'長野県'   =>  '長野県',
								'岐阜県'   =>  '岐阜県',
								'静岡県'   =>  '静岡県',
								'愛知県'   =>  '愛知県',
								'三重県'   =>  '三重県',
								'滋賀県'   =>  '滋賀県',
								'京都府'   =>  '京都府',
								'大阪府'   =>  '大阪府',
								'兵庫県'   =>  '兵庫県',
								'奈良県'   =>  '奈良県',
								'和歌山県' =>  '和歌山県',
								'鳥取県'   =>  '鳥取県',
								'島根県'   =>  '島根県',
								'岡山県'   =>  '岡山県',
								'広島県'   =>  '広島県',
								'山口県'   =>  '山口県',
								'徳島県'   =>  '徳島県',
								'香川県'   =>  '香川県',
								'愛媛県'   =>  '愛媛県',
								'高知県'   =>  '高知県',
								'福岡県'   =>  '福岡県',
								'佐賀県'   =>  '佐賀県',
								'長崎県'   =>  '長崎県',
								'熊本県'   =>  '熊本県',
								'大分県'   =>  '大分県',
								'宮崎県'   =>  '宮崎県',
								'鹿児島県' =>  '鹿児島県',
								'沖縄県'   =>  '沖縄県'
							  );
							  
							  
	//リターンメール 送信時
	$RETURN_MAIL_STATUS_COMPANY_REG 		=1 ;
	$RETURN_MAIL_STATUS_COMPANY_REG_OK 		=2 ;
	$RETURN_MAIL_STATUS_COMPANY_CHANGE 		=3 ;
	$RETURN_MAIL_STATUS_COMPANY_CHANGE_OK 	=4 ;
	$RETURN_MAIL_STATUS_JOB_REG 			=5 ;
	$RETURN_MAIL_STATUS_JOB_REG_OK 			=6 ;
	$RETURN_MAIL_STATUS_JOB_CHANGE 			=7 ;
	$RETURN_MAIL_STATUS_JOB_CHANGE_OK 		=8 ;
	$RETURN_MAIL_STATUS_USER_REG 			=9 ;
	$RETURN_MAIL_STATUS_USER_CHANGE 		=10 ;
	$RETURN_MAIL_STATUS_USER_PASSWORD 		=11 ;

	// メールドメイン配列
	$MAIL_DOMAIN_ARRAY = array(
				'0' => '.aero',
				'1' => '.biz',
				'2' => '.bz',
				'3' => '.cc',
				'4' => '.cn',
				'5' => '.com',
				'6' => '.coop',
				'7' => '.info',
				'8' =>'.jp',
				'9' => '.co.jp',
				'10' => '.name',
				'11' => '.ne.jp',
				'12' => '.net',
				'13' => '.org',
				'14' => '.pro',
				'15' => '.tv',
				'16' => '.tw',
				'17' => '.vc'
	);

// =========================================================================
// セレクトボックスの生成（ 1択用 ）
// -------------------------------------------------------------------------
// $index        : 表示するリスト。配列
// $formName     : フォームの名前
// $selectedValue: selectedの値
//               :
// 戻り値        : セレクトボックスのHTML
// =========================================================================
function createSelectForm($index, $formName, $selectedValue) {

	$resultHTML = " <select name='".$formName."' id='".$formName."'>\n";

	if(count($index) == 0) {
		$index[] = "void";	// 配列が空だとエラーになるので強制代入
	}
	foreach($index as $key => $value) {
		if( $key==$selectedValue ) {
			$selected = " selected='selected'";		// 選択されている場合
		} else {
			$selected = "";
		}
		$resultHTML .= " <option value='".$key."'".$selected.">".$value."</option>\n";
	}
	$resultHTML .= " </select> ";

	return $resultHTML;
}

// *************************************************************************
// コンボボックスの生成
// -------------------------------------------------------------------------
// $index        : 表示するリスト。配列。
// $formName     : フォームの名前。
// $selected     : selectedにする値。
// $class	     : クラス名
// $disabled     : 1=編集不可にする
//               :
// 戻り値        : コンボボックスのHTML
// *************************************************************************
function createComboboxForm($index, $formName, $selected, $class, $required, $disabled='') {

	if( is_array($index) ){
		//$disabled = $disable==1 ? " disabled='disabled'": '';
		//$required = $require==1 ? " required'": '';
		
		$resultHTML = '<select'.$disabled.' name="'.$formName.'" id="'.$formName.'"'.$class.$required.'>'."\n";
	
		$i = 0;
		foreach($index as $key => $value) {
			// 選択されている場合
			if($key == $selected) {
				$select = " selected='selected'";
			} else {
				$select = "";
			}
			$resultHTML .= "<option value='".$key."'".$select.">".$value."</option>\n";
			$i++;
		}

		$resultHTML .= "</select>\n";
		return $resultHTML;
	}
}

// *************************************************************************
// ラジオボタンの生成
// -------------------------------------------------------------------------
// $index        : 表示するリスト。配列。
// $formName     : フォームの名前。
// $checked      : checkedにする値。
// $type	     : inputの間に何をいれるか  0=半角スペース  1=改行  2=<li>で囲む
// $require      : 1:必須   その他：必須ではない
// 戻り値        : ラジオボタンのHTML
// *************************************************************************
function createRadioForm2($index, $formName, $checked, $type=0, $require=0, $class) {

	//必須項目
	if( $require==1 ){
		$req = ' required';
	}else{
		$req = '';
	}
	$id_str = ' id="'.$formName.'"';
		
	foreach($index as $key => $value) {
		
		// 選択されている場合
		if($key == $checked) {
			$checked2 = "checked";
		} else {
			$checked2 = "";
		}
		
		if($type == 0){
			$resultHTML .= "<label><input type='radio' name='".$formName."'".$id_str." value='".$key."' ".$checked2.$req.">&nbsp;".$value."</label>&nbsp;\n";
		}else if($type == 1){
			$resultHTML .= "<label><input type='radio' name='".$formName."'".$id_str." value='".$key."' ".$checked2.$req.">&nbsp;".$value."</label><br>\n";
		}else if($type == 2){
			$resultHTML .= "<li><label><input type='radio' name='".$formName."'".$id_str." value='".$key."' ".$checked2.$req.">&nbsp;".$value."</label></li>\n";
		}
		$id_str = '';
		$req = '';
	}

	return $resultHTML;
}

// *************************************************************************
// 全角/半角スペースだけの入力なら空文字にする
// 前後の全角/半角スペースは削除
// NULバイト削除
// *************************************************************************
function makeNoSpString($str) {
	$str = trim($str);
	$str = preg_replace('/^[ 　]+/u', '', $str);
	$str = preg_replace('/[ 　]+$/u', '', $str);
	return $str;
	//return trim2($str);
}

// *************************************************************************
// 強化型trim
// 全角/半角スペースだけの入力なら空文字にする
// *************************************************************************
function trim2($str) {

	$str = trim($str);
	$spaceCnt1 = substr_count($str,"　");	// 全角スペースのカウント
	$spaceCnt2 = substr_count($str," ");	// 半角スペースのカウント
	$strCnt	   = mb_strlen($str);			// 引数の文字数

	// 全部全角スペースなら空文字にする
	if($strCnt == $spaceCnt1) {
		$str = "";
	}

	// 全角と半角スペースのみの混合なら空文字にする
	if($strCnt == ($spaceCnt1+$spaceCnt2)) {
		$str = "";
	}

	return $str;
}


// *************************************************************************
// 改行を削除
// *************************************************************************
function makeNoLastFoodCodeString($str) {
	return str_replace(array("\r\n","\n","\r"), "", $str);	// 最後の改行を削除
}

// *************************************************************************
// 特殊文字を HTML エンティティに変換する
//		テキストをHTMLに表示際に使う						
// *************************************************************************
function htmlDisplayText( $txt ){
	//return htmlspecialchars($txt,ENT_QUOTES, 'UTF-8');
	return htmlspecialchars($txt,ENT_QUOTES);
}

// *************************************************************************
// データベースTEXT型の改行コードを<br>に変換
// -------------------------------------------------------------------------
// $text  : 変換対象の文字列
//        :
// 戻り値 : 変換した文字列
// *************************************************************************
function crReplace($text) {
	return str_replace( "\r\n" , '<br>' , $text);
}


// *************************************************************************
// テキストエリア内でのエスケープ
// -------------------------------------------------------------------------
// $text  : 変換対象の文字列
//        :
// 戻り値 : 変換した文字列
// *************************************************************************
function esc_textarea( $text ) {
  $safe_text = htmlspecialchars( $text, ENT_QUOTES );
  $safe_text = nl2br( $safe_text);
  $safe_text = str_replace("<br />", "<br>", $safe_text);
  return  $safe_text;
}


// *************************************************************************
// 全角平仮名とカタカナのみ　true を返す
// *************************************************************************
function kanaHiragana_check($str) {
	return preg_match("/^[ぁ-んァ-ヶー 　]+$/u", $str );
}

// *************************************************************************
// ゆるいメールアドレスチェック
// *************************************************************************
function mail_checkk($mail){
	return preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/",$mail );
}

// *************************************************************************
// 郵便番号チェック
// *************************************************************************
function zip_checkk($str) {
	return preg_match("/^([0-9]{3}-[0-9]{4})?$|^[0-9]{7}+$/", $str);
}

// *************************************************************************
// 電話番号チェック　　※海外の番号含む　+81とか
// *************************************************************************
function tel_checkk($str) {
	return preg_match("/^([0-9+])+([0-9\-])+([0-9])+$/",$str );
}
// *************************************************************************
// 電話番号チェック2　　※海外の番号含む　+81とか ()も含む
// *************************************************************************
function tel_checkk2($str) {
	$str = kakko_changeToHalf($str);		//カッコらしきものは半角()に統一
	$str = hyphen_changeToHalf($str);		//カッコらしきものは半角()に統一
	return preg_match("/^([0-9+(])+([0-9\-()])+([0-9])+$/",$str );
}

// *************************************************************************
// $target配列にあるハイフンのようなものは半角ハイフンに変換
// *************************************************************************
function hyphen_changeToHalf($str) {
	$target = array('-', '﹣', '－', '−', '⁻', '₋','‐', '‑', '‒', '–', '—', '―', '﹘');
	$to = '-';
	return str_replace($target, $to, $str);
}

// *************************************************************************
// $target配列にある()は半角()に変換
// *************************************************************************
function kakko_changeToHalf($str) {
	$target1 = array('（', '【', '〔','［', '〈');
	$target2 = array('）', '】', '〕', '］', '〉');
	$to1 = '(';
	$to2 = ')';
	$str =  str_replace($target1, $to1, $str);
	return str_replace($target2, $to2, $str);
}