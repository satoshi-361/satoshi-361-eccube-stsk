<?php
require "phpmailer/PHPMailerAutoload.php";

mb_language(MAIL_PHP_LANGUAGE);
mb_internal_encoding(MAIL_PHP_INTERNAL_ENCODING);

define('MAIL_BASE_TEMPLATE','mail_include/mail_template.txt');


//---------------------------------------------------------------------	
// お問合せ
//---------------------------------------------------------------------	
function sendMailContact( $aryPost ){
	global $MAIL_BASE_TEMPLATE;
	
	$return = FALSE;
	if( is_array( $aryPost ) ){
		$body = file_get_contents(MAIL_BASE_TEMPLATE);

		foreach( $aryPost as $key => $str ){
			$body = str_replace( '{#'.$key.'#}',$str,$body );
		}
		
		$ip = getenv("REMOTE_ADDR");				// IPアドレスの取得
		$host = getenv("REMOTE_HOST");				// ホスト名の取得
		if($host == null || $host == $ip){
			$host = gethostbyaddr($ip);
		}
		$body = str_replace( '{#ip#}', $ip, $body );
		$body = str_replace( '{#host#}', $host, $body );
		$body = str_replace( '{#browser#}', $_SERVER['HTTP_USER_AGENT'] ,$body );
		$body = str_replace( '{#datenow#}', date("Y/m/d H:i:s"), $body );
		
		$subject = "【ジョブカードセンター】ホームページからのお問合せ";
		
		//管理者メール送信		
		$mail = new PHPMailer();					//PHPMailerのインスタンス生成
		$mail->CharSet	= MAIL_CHARSET;				//文字コード設定
		$mail->Encoding	= MAIL_ENCODING;			//エンコーディング
		$mail->From		= MAIL_FROM;													//差出人(From)をセット
		$mail->FromName = mb_encode_mimeheader(MAIL_FROM_NAME);							//差出人(From名)をセット
		$mail->AddAddress(MAIL_TO);														//宛先(To)をセット
		$mail->Subject	= mb_encode_mimeheader($subject);								//件名(Subject)をセット
		$mail->Body		= mb_convert_encoding($body,"JIS",MAIL_PHP_INTERNAL_ENCODING);						//本文(Body)をセット
		$return = $mail->Send();
	
		unset($mail);
	}
	return $return;
}