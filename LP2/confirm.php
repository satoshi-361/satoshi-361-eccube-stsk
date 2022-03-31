<?php
require "common/mail_include/formMail_min.php";
require "common/mail_include/item.php";
require "common/phpmailer/PHPMailerAutoload.php";

mb_language(MAIL_PHP_LANGUAGE);
mb_internal_encoding(MAIL_PHP_INTERNAL_ENCODING);

$pagekey = 'contact';

	
if( isset($_SESSION[$pagekey]['post']) ){
	$post = $_SESSION[$pagekey]['post'];
	
	if($_SERVER["REQUEST_METHOD"]==="POST" ){
				
		$your_name	= $post['your_name'];			// お名前
		$tel_no		= $post['tel_no'];				// 電話番号
		$mail_addr	= $post['mail_addr'];			// メールアドレス
		$cmt		= $post['cmt_body'];			// お問合せ内容
		
		$ip = getenv("REMOTE_ADDR");				// IPアドレスの取得
		$host = getenv("REMOTE_HOST");				// ホスト名の取得
		if($host == null || $host == $ip){
			$host = gethostbyaddr($ip);
		}
		$browser = $_SERVER['HTTP_USER_AGENT'];		// ブラウザ情報
		$datenow = date("Y/m/d H:i:s");
		
		//1通目　問合せメール
		$subject = "【株式会社秋冬春夏 HP】お問い合せ";
		$body = <<< STRING

【株式会社秋冬春夏のWebサイト】からお問い合わせがありました。
===============================================================

■お名前
$your_name

■電話番号
$tel_no

■メールアドレス
$mail_addr

■お問合せ内容
$cmt




■送信情報
日時：$datenow
IP：$ip
ホスト：$host
ユーザーエージェント：$browser


**************************************************
株式会社秋冬春夏
〒116-0002
東京都荒川区荒川5-31-20三祥新館ビル3F
TEL : 03-5901-9633
FAX : 03-5901-9634
https: //www.s-t-s-k.com/
**************************************************

STRING;
		//2通目　問い合わせした方へ内容控えメール
		$subject1 = "【株式会社秋冬春夏 HP】お問い合せありがとうございます";
		$body1 = <<< STRING
アドレス： goods@s-t-s-k.com
件名： 【株式会社秋冬春夏】縫製グッズお問い合せ
本文：

※本メールは自動配信メールです。

この度は、お問い合せいただき誠にありがとうございます。
改めて担当者よりご連絡させていただきます。
通常2営業日以内にご連絡いたしますが、内容によってはお時間をいただく場合もございます。

ご不明な点などがございましたら、お気軽にお知らせください。

■お名前
$your_name


■電話番号
$tel_no


■メールアドレス
$mail_addr


■お問合せ内容
$cmt


------------------------------------------
《ノベルティの春夏秋冬》
URL：http://www.s-t-s-k.com/
Mail：goods@s-t-s-k.com

株式会社秋冬春夏
住所：〒114-0012
東京都北区田端新町1-7-8相光ビル3F
TEL：03-5901-9633 ／ FAX：03-5901-9634
受付時間：10:00～17:00(定休日：土日祝祭日)
------------------------------------------

※本メールにお心当たりがない場合は、お手数ですが(goods@s-t-s-k.com)までご連絡ください。

STRING;
		
		$mail = new PHPMailer();							//PHPMailerのインスタンス生成
		$mail->CharSet	= MAIL_CHARSET;						//文字コード設定
		$mail->Encoding	= MAIL_ENCODING;					//エンコーディング
		$mail->From = MAIL_FROM;																	//差出人(From)をセット
		$mail->FromName = mb_encode_mimeheader(mb_convert_encoding(MAIL_FROM_NAME,"JIS","UTF-8"));	//差出人(From名)をセット
		
		// 管理者へお問合せメール送信
		$mail->AddAddress(MAIL_TO);																	//宛先(To)をセット
		$mail->Subject = mb_encode_mimeheader(mb_convert_encoding($subject,"JIS","UTF-8"));			//件名(Subject)をセット
		$mail->Body  = mb_convert_encoding($body,"JIS","UTF-8");									//本文(Body)をセット
		$return = $mail->Send();	//メールを送信
		
		// お問合せ者様へ控えのメール送信
		if( $return ) {
			$mail->ClearAddresses();
			$mail->AddAddress($mail_addr);															//宛先(To)をセット
			$mail->Subject = mb_encode_mimeheader(mb_convert_encoding($subject1,"JIS","UTF-8"));	//件名(Subject)をセット
			$mail->Body  = mb_convert_encoding($body1,"JIS","UTF-8");								//本文(Body)をセット
			$return = $mail->Send();
		}

		unset($_SESSION[$pagekey]);
		$r = $return==false ? 0 : 1;
		header("Location:./complete.php?r=".$r);
		exit();
	}
	else{
		//HTML形成
		foreach( $ITEMS_ARRAY as $key => $ary ) {
			$class = strlen($ary['class'])>0 ? ' class="'.$ary['class'].'"': '';
			$require = filter_var($ary['require'], FILTER_VALIDATE_BOOLEAN) ? ' required' : '';
			$placeholder = strlen($ary['placeholder'])>0 ? ' placeholder="'.$ary['placeholder'].'"' : '';
			
			switch( $ary['type'] ){
				case 'text':
				case 'tel':
				case 'email':
					$str_html[$key] = htmlDisplayText($post[$key]);
					break;
				case 'textarea':
					$str_html[$key] = esc_textarea($post[$key]);
					break;
				case 'select':
					if( is_array($post[$key]) ){
						$str_html[$key]='';
						foreach($post[$key] as $k => $v) {
							if( strlen($str_html[$key])>0 ){
								$str_html[$key] .= ' , ';
							}
							$str_html[$key] .= $v;
						}
					}else{
						$str_html[$key] = $v;
					}
					break;
				case 'radio':
					$str_html[$key] = htmlDisplayText($post[$key]);
					break;
				default:
					break;
			}
		}
	}
}else{
	unset($_SESSION[$pagekey]);
	header("Location:./error.html");
	exit();
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title> ノベルティオリジナル制作・販促グッズ・ノベルティ名入れなら株式会社秋冬春夏</title>
<meta name="description" content="株式会社秋冬春夏はＯＥＭの雑貨、ＯＥＭの玩具等の輸入・企画・開発・制作、キャラクター商品の企画・管理・制作・販売/ノベルティの企画・開発・販促グッズ/印刷業務/郵便物の発送代行業を行っております。">
<meta name="keywords" content="ノベルティ名入れ、ノベルティオリジナル、ノベルティ販促、販促グッズ、ノベルティ制作">

<link rel="stylesheet" href="common/css/contents.css" type="text/css">
<link rel="stylesheet" href="common/css/toppage.css" type="text/css">

<!--[if lt IE 9]>
<script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript" src="common/js/scroll.js"></script>
</head>
<body>
	<header id="header">
		<div class="wrapper">
			<h1><a href="./"><img src="img/logo.jpg" alt="株式会社秋冬春夏"></a></h1>
			<p class="btn_cntact"><a href=""><img src="img/header_btn_contact.png" alt="24時間受付中　無料のご相談"></a></p>
			<p class="tel"><img src="img/header_tel.png" alt="24時間受付中　無料のご相談"></p>
		</div>
	</header>
	
	<div id="wrapper">
		<article class="box_red">
			<div id="frameContact">
				<h2><img src="img/title_contact.png" alt="お問合せフォーム"</h2>
				<form name="contact_form" action="confirm.php" method="post">
					<dl class="conf">
						<dt>お名前<span class="nes">必須</span></dt>
						<dd><?=$str_html["your_name"]; ?></dd>
						<dt>メールアドレス<span class="nes">必須</span></dt>
						<dd><?=$str_html["mail_addr"]; ?></dd>
						<dt>お電話番号<span class="nes">必須</span></dt>
						<dd><?=$str_html["tel_no"]; ?></dd>
						<dt>お問い合わせ内容<span class="nes">必須（500文字まで）</span></dt>
						<dd><?=$str_html["cmt_body"]; ?></dd>
					</dl>
					<p class="submit_btn">
						<input type="button" onclick="location.href='index.php?p=back#frameContact'" value="修正する" class="btn_s">
						<input name="commit" type="submit" value="送信する" class="btn_s">
					</p>
				</form>
			</h2>
		</article>
		
		<footer id="footer" class="clearfix">
			<table class="headOffice lineBtm">
				<tr>
					<th>会社名</th>
					<td>株式会社 秋冬春夏</td>
				</tr>
				<tr>
					<th>所在地</th>
					<td>〒116-0002　東京都荒川区荒川5-31-20三祥新館ビル3F</td>
				</tr>
				<tr>
					<th>代表番号</th>
					<td>03-5901-9633</td>
				</tr>
				<tr>
					<th>FAX</th>
					<td>03-5901-9634</td>
				</tr>
				<tr>
					<th>代表者</th>
					<td>有馬正悟</td>
				</tr>
				<tr>
					<th>決算期</th>
					<td>12月</td>
				</tr>
				<tr>
					<th>業務内容</th>
					<td>
					  <ul>
						<li>縫製関連製造</li>
						<li>縫製商品昇華転写業務</li>
						<li>雑貨、玩具等の輸入業務及び企画開発</li>
					  </ul>
					</td>
				</tr>
			</table>
			<div class="frameMap">
				<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d809.6068501669971!2d139.77337522711306!3d35.740298380180555!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x60188e75e2e7a9dd%3A0xb4437c9206459bdd!2z44CSMTE2LTAwMDIg5p2x5Lqs6YO96I2S5bed5Yy66I2S5bed77yV5LiB55uu77yT77yR4oiS77yS77yQ!5e0!3m2!1sja!2sjp!4v1516345692905" width="350" height="350" frameborder="0" style="border:0" allowfullscreen></iframe>
				<p class="copyright">&copy; Copyright 2018 SHU-TOU-SHUN-CA. All Rights Reserved.</p>
			</div>
		</footer>
	</div>
	
</body>
</html>
