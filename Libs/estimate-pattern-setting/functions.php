<?php
require_once '../../../../wp-load.php';
require_once '../../usc-e-shop/usc-e-shop.php';

/**
 * 「注文する」ボタン押下時ログインしていない場合はログイン画面に遷移する
 */
function custom_template_redirect () {
	unset($_SESSION['item_page']);
	$_SESSION['item_page'] = $_SERVER["HTTP_REFERER"];

	global $post;
	if(isset($_POST['IsOrderFlg']) && !usces_is_login()) {
		$loginUrl = home_url() . '/usces-member/?usces_page=login';
		wp_safe_redirect($loginUrl);
		exit;
	}
}

/** 
 * 注文時のバリデーション
 */
function aiosl_validation( $target, $checkArr ) {
	$errMsg = [];
    foreach($target as $key => $val) {
        // EmptyCheck
        if(in_array($key, $checkArr)) {
            if(empty($val)) {
				$errMsg[$key] = "入力してください";
            }
        }
		// 形式チェック(メールアドレス)
		if ($key == 'mailaddress1' && !empty($val) || $key == 'nonMember_mailaddress1' && !empty($val) ) {
			$reg_str = "/^([a-zA-Z0-9])+([a-zA-Z0-9._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9._-]+)+$/";
			if (!preg_match($reg_str, $val)) {
				$errMsg[$key] = "メールアドレスの形式が正しくありません";
			}
		}
		// 形式チェック(電話・FAX番号)
		if ($key == 'tel' || ($key == 'fax' && !empty($val)) || $key == 'nonMember_tel' || ($key == 'nonMember_fax' && !empty($val))) {
			$reg_str = "/^[0-9]{2,4}-[0-9]{2,4}-[0-9]{3,4}$/";
			if (!preg_match($reg_str, $val)) {
				$errMsg[$key] = "電話番号・FAX番号の形式が正しくありません";
			}
		}
		// 形式チェック(郵便番号)
		if ($key == 'zipcode' || $key == 'nonMember_zipcode') {
			$reg_str = "/^[0-9]{3}-[0-9]{4}$/";
			if (!preg_match($reg_str, $val)) {
				$errMsg[$key] = "郵便番号番号の形式が正しくありません";
			}
		}	
        // 置換
        if($key == 'name1' || $key == 'name2' || $key == 'address1' || $key == 'address2' || $key == 'address3' ||
			$key == 'nonMember_name1' || $key == 'nonMember_name2' || $key == 'nonMember_address1' || $key == 'nonMember_address2' || $key == 'nonMember_address3') {
            $patterns = [ "/\\\'/", '/\\\"/', '/\\\\/', '/</', '/>/' ];
            $replacements = [ '‘', '”', '￥', '＜', '＞' ];
            ksort($patterns);
            ksort($replacements);
            $target[$key] = preg_replace($patterns, $replacements, $val);
        }
    }
	$result = [
		'target' => $target,
		'errMsg' => $errMsg
	];
	return $result;
}