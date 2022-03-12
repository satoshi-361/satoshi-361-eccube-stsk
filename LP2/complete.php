<?php
	if( filter_input(INPUT_GET,'r', FILTER_VALIDATE_BOOLEAN) ){
		$msg = '<p class="textCenter">お問合せ有難うございました。<br>メールは無事送信されました。</p><p class="pt30">後日に担当者より回答させて頂きますが、もし届かない場合はお客様のメールの設定でスパムメールとして扱われている可能性がございます。<br>その際は大変恐縮では御座いますが、設定をご確認の上再度お問い合わせ頂きますようお願い申し上げます。</p>';
	}else{
		$msg = "<p>メール送信に失敗しました。<br>お手数ですが、再度お問合せいただきますようお願いいたします。</p>";
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
			<h1><a href="http://www.s-t-s-k.com/"><img src="img/logo.jpg" alt="株式会社秋冬春夏"></a></h1>
			<p class="btn_cntact"><a href=""><img src="img/header_btn_contact.png" alt="24時間受付中　無料のご相談"></a></p>
			<p class="tel"><img src="img/header_tel.png" alt="24時間受付中　無料のご相談"></p>
		</div>
	</header>
	
	<div id="wrapper">
		<article class="box_red">
			<div id="frameContact">
				<h2><img src="img/title_contact.png" alt="お問合せフォーム"></h2>
				<div class="box_msg">
					<h3>お問い合せが完了しました</h3>
					<?=$msg;?>
					<p class="pt30 textCenter"><a href="./">&raquo; トップページへ戻る</a></p>
				</div>
			</div>
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
	
	<!-- Google Code for web Conversion Page -->
	<script type="text/javascript">
	/* <![CDATA[ */
	var google_conversion_id = 820854538;
	var google_conversion_label = "J9TBCOm753sQiv60hwM";
	var google_remarketing_only = false;
	/* ]]> */
	</script>
	<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
	</script>
	<noscript>
	<div style="display:inline;">
	<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/820854538/?label=J9TBCOm753sQiv60hwM&amp;guid=ON&amp;script=0"/>
	</div>
	</noscript>
	<!-- リマーケティング タグの Google コード -->
	<script type="text/javascript">
	/* <![CDATA[ */
	var google_conversion_id = 820854538;
	var google_custom_params = window.google_tag_params;
	var google_remarketing_only = true;
	/* ]]> */
	</script>
	<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
	</script>
	<noscript>
	<div style="display:inline;">
	<img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/820854538/?guid=ON&amp;script=0"/>
	</div>
	</noscript>
</body>
</html>
