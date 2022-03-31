<?php
session_start();

function ctob(){

    mb_language("Ja") ;  
    mb_internal_encoding("UTF-8") ;  
    $mailto="info@s-t-s-k.com";
    $name = $_POST['name'];
	$email = $_POST['email'];
	$phone = $_POST['phone'];
	$inq = $_POST['inq'];
	$from = "From:".$email;
    $subject = "ランディングページにお問い合わせがありました";
    $content = "ランディングページ【　http://www.s-t-s-k.com/lp/　】に\nお客様からお問い合わせがありました。\n※本メールは、プログラムから自動で送信しています。\n\n以下お客様情報です。\n−−−−−−−−−−−−−−−−−−−−−−−−−−\n\nお名前：$name\n\nメールアドレス：$email\n\nお電話番号：$phone\n\nお問い合わせ内容：\n$inq\n\n−−−−−−−−−−−−−−−−−−−−−−−−−−\n\nお客様への折り返しのご連絡を宜しくお願い致します。";
    mb_language("Ja") ;  
    mb_internal_encoding("UTF-8") ; 
    mb_send_mail($mailto,$subject,$content,$from,"-f".$email); 
}

function btoc(){
    mb_language("Ja") ;  
    mb_internal_encoding("UTF-8") ;  
    $mailto = $_POST['email'];
    $name = $_POST['name'];
	  $email = $_POST['email'];
	  $phone = $_POST['phone'];
	  $inq = $_POST['inq'];
    $subject = "お問い合わせいただきありがとうございます";
    // $content = "【　".$name."　】様\n\nこの度は【　株式会社 秋冬春夏　】の\nランディングページ【　http://www.s-t-s-k.com/lp/ 】より\nお問い合わせいただき誠にありがとうございます。\n\n下記の内容をご確認させて頂いた後、\n折り返し担当よりご連絡をさせていただきます。\n宜しくお願いします。\n\n−−−−−−−−−−−−−−−−−−−−−−−−−−\n\nお名前：$name\n\nメールアドレス：$email\n\nお電話番号：$phone\n\nお問い合わせ内容：\n$inq\n\n−−−−−−−−−−−−−−−−−−−−−−−−−−\n\n尚、3日経ってもご連絡がない場合、\n何かの問題でメールが届いていない可能性があります。\n大変恐縮ですが、その際は下記お電話番号まで\nご連絡をいただけますと幸いです。\n【TEL：03-5901-9633】\n\n※本メールは、プログラムから自動で送信しています。\n心当たりの無い方は、お手数ですが削除してください。\nもしくは、そのまま送信元に返信していただければと思います。\n===============================================\n\n\n株式会社 秋冬春夏\n〒116-0002　東京都荒川区荒川5-31-20三祥新館ビル3F\nTEL：03-5901-9633\n===============================================";
	  $content = "アドレス： goods@s-t-s-k.com\n"
          . "件名：　【株式会社秋冬春夏】アクリルグッズお問い合せ\n"
          . "本文：\n\n"
          . "※本メールは自動配信メールです。\n\n"
          . "この度は、お問い合せいただき誠にありがとうございます。\n"
          . "改めて担当者よりご連絡させていただきます。\n"
          . "通常2営業日以内にご連絡いたしますが、内容によってはお時間をいただく場合もございます。\n\n"
          . "ご不明な点などがございましたら、お気軽にお知らせください\n\n"
          . "お名前：$name\n\n"
          . "メールアドレス：$email\n\n"
          . "お電話番号：$phone\n\n"
          . "お問い合わせ内容：\n$inq\n\n"
          . "------------------------------------------\n"
          . "URL：http://www.s-t-s-k.com/\n"
          . "Mail：goods@s-t-s-k.com\n"
          . "株式会社秋冬春夏\n"
          . "住所：〒114-0012\n"
          . "東京都北区田端新町1-7-8相光ビル3F\n"
          . "TEL：03-5901-9633 ／ FAX：03-5901-9634\n"
          . "受付時間：10:00～17:00(定休日：土日祝祭日)\n"
          . "------------------------------------------\n\n"
          . "※本メールにお心当たりがない場合は、お手数ですが(goods@s-t-s-k.com)までご連絡ください。\n";
    $from = "From:info@s-t-s-k.com";
	  $f = "-finfo@s-t-s-k.com";
	  mb_language('uni');
    mb_internal_encoding('UTF-8');
    mb_send_mail($mailto,$subject,$content,$from,$f); 
}

if($_SESSION['lc'] != 'no'){
	ctob();
	btoc();
	$_SESSION['lc'] = 'no';
};?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title></title>
<meta name="keywords" content="">
<meta name="description" content="">
<link rel="stylesheet" type="text/css" href="./css/reset.css">
<link rel="stylesheet" type="text/css" href="./css/base.css">
<script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="js/common.js"></script>

<script>
 (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
 (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
ga('create', 'UA-9490839-1', 'auto');
ga('send', 'pageview');
</script>

<!-- Global site tag (gtag.js) - Google Ads: 670372701 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-670372701"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-670372701');
</script>
<!-- Event snippet for 問い合わせ完了 conversion page -->
<script>
  gtag('event', 'conversion', {'send_to': 'AW-670372701/ky8NCOTJ_L8BEN2m1L8C'});
</script>

</head>
<body>
<div class="form">
  <div class="form_inner">
    <p>ただいまお問い合わせを多数頂いておりご相談いただくタイミングによっては<br />
      少しお時間を頂戴してしまう可能性がございます。<br />
      ただし、お問い合わせをいただけましたら必ず24時間以内にはご返信をいたします。</p>
  
    <div class="bg_form_inner" style="margin-top:30px;">
      <p class="thanks">お問い合せを頂きまして<br />誠にありがとうございました。</p>
      <p class="thanks_txt">
内容をご確認させて頂いた後、<br />
折り返し担当よりご連絡をさせていただきます。<br />
宜しくお願いします。<br /><br />
<a href="http://www.s-t-s-k.com/lp/">LPトップへ戻る>></a></p>
    </div>
  </div>
</div>
<div class="footer">
  <div class="footer_inner">
    <div class="company_left">
      <div class="companybox">
        <dl>
          <dt>会社</dt>
          <dd>株式会社 秋冬春夏</dd>
        </dl>
      </div>
      <div class="companybox">
        <dl>
          <dt>所在地</dt>
          <dd>〒116-0002　東京都荒川区荒川5-31-20三祥新館ビル3F</dd>
        </dl>
      </div>
      <div class="companybox">
        <dl>
          <dt>代表番号</dt>
          <dd>03-5901-9633</dd>
        </dl>
      </div>
      <div class="companybox">
        <dl>
          <dt>FAX</dt>
          <dd>03-5901-9634 </dd>
        </dl>
      </div>
      <div class="companybox">
        <dl>
          <dt>代表者</dt>
          <dd>有馬正悟 </dd>
        </dl>
      </div>
      <div class="companybox">
        <dl>
          <dt>決算期</dt>
          <dd>12月 </dd>
        </dl>
      </div>
      <div class="companybox">
        <dl>
          <dt>業務内容</dt>
          <dd>雑貨、玩具等の輸入及び企画開発業務<br />
            雑貨、玩具等の販売、管理業務<br />
            販促物（ノベルティー）の企画、製作、販売業務<br />
            キャラクター商品のライセンス管理、企画、製作、販売業務<br />
            印刷業務<br />
            郵便物の発送業務 </dd>
        </dl>
      </div>
    </div>
    <div class="company_right">
      <p>
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d898.3184272741!2d139.77342498781962!3d35.73999896705921!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzXCsDQ0JzI1LjAiTiAxMznCsDQ2JzI2LjEiRQ!5e0!3m2!1sja!2sjp!4v1464698133094" width="350" height="350" frameborder="0" style="border:0" allowfullscreen></iframe>
      </p>
      <div class="copyright">
        <p> © Copyright 2009 SHU-TOU-SHUN-CA. All Rights Reserved.</p>
      </div>
    </div>
  </div>
</div>

<!-- Yahoo Code for your Conversion Page -->
<script type="text/javascript">
    /* <![CDATA[ */
    var yahoo_conversion_id = 1000410544;
    var yahoo_conversion_label = "2mmNCKrunXkQo4HIigM";
    var yahoo_conversion_value = 0;
    /* ]]> */
</script>
<script type="text/javascript" src="https://s.yimg.jp/images/listing/tool/cv/conversion.js">
</script>
<noscript>
    <div style="display:inline;">
        <img height="1" width="1" style="border-style:none;" alt="" src="https://b91.yahoo.co.jp/pagead/conversion/1000410544/?value=0&label=2mmNCKrunXkQo4HIigM&guid=ON&script=0&disvt=true"/>
    </div>
</noscript>
<script type="text/javascript" language="javascript">
  /* <![CDATA[ */
  var yahoo_ydn_conv_io = "1kJW.dgOLDXRhRnwbsFF";
  var yahoo_ydn_conv_label = "01EKUZQFHTQBAFHGD8X402967";
  var yahoo_ydn_conv_transaction_id = "";
  var yahoo_ydn_conv_amount = "0";
  /* ]]> */
</script>
<script type="text/javascript" language="javascript" charset="UTF-8" src="https://b90.yahoo.co.jp/conv.js"></script>




<!-- Google Code for &#12362;&#21839;&#21512;&#12379; Conversion Page -->
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 880771034;
var google_conversion_language = "en";
var google_conversion_format = "3";
var google_conversion_color = "ffffff";
var google_conversion_label = "y3PcCNrgjGcQ2v_9owM";
var google_remarketing_only = false;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/880771034/?label=y3PcCNrgjGcQ2v_9owM&amp;guid=ON&amp;script=0"/>
</div>
</noscript>

<!-- リマケ-->
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 880771034;
var google_custom_params = window.google_tag_params;
var google_remarketing_only = true;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/880771034/?value=0&amp;guid=ON&amp;script=0"/>
</div>
</noscript>

<!-- Google Code for &#38651;&#35441; Conversion Page In your html page, add the snippet and call goog_report_conversion when someone clicks on the phone number link or button. -->
<script type="text/javascript">
/* <![CDATA[ */
goog_snippet_vars = function() {
var w = window;
w.google_conversion_id = 880771034;
w.google_conversion_label = "KA5ACPXBrWcQ2v_9owM";
w.google_remarketing_only = false;
}
// DO NOT CHANGE THE CODE BELOW.
goog_report_conversion = function(url) {
goog_snippet_vars();
window.google_conversion_format = "3";
var opt = new Object();
opt.onload_callback = function() {
if (typeof(url) != 'undefined') {
window.location = url;
}
}
var conv_handler = window['google_trackConversion'];
if (typeof(conv_handler) == 'function') {
conv_handler(opt);
}
}/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion_async.js"></script>

<!-- wonder-->
<script type="text/javascript">
(function(i,s,o,g,r,a,m){i['AdObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//tracking.wonder-ma.com/tk/t.js','at');
at('send','373,433');
</script> 
<!-- Google Code for &#12362;&#21839;&#12356;&#21512;&#12431;&#12379; Conversion Page -->
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 871406006;
var google_conversion_language = "en";
var google_conversion_format = "3";
var google_conversion_color = "ffffff";
var google_conversion_label = "vjIHCLnu62oQtrPCnwM";
var google_conversion_value = 1000.00;
var google_conversion_currency = "JPY";
var google_remarketing_only = false;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/871406006/?value=1000.00&amp;currency_code=JPY&amp;label=vjIHCLnu62oQtrPCnwM&amp;guid=ON&amp;script=0"/>
</div>
</noscript>
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 871406006;
var google_custom_params = window.google_tag_params;
var google_remarketing_only = true;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/871406006/?guid=ON&amp;script=0"/>
</div>
</noscript>


<!-- Yahoo Code for your Target List -->
<script type="text/javascript" language="javascript">
/* <![CDATA[ */
var yahoo_retargeting_id = '1S0IKTVW30';
var yahoo_retargeting_label = '';
var yahoo_retargeting_page_type = '';
var yahoo_retargeting_items = [{item_id: '', category_id: '', price: '', quantity: ''}];
/* ]]> */
</script>
<script type="text/javascript" language="javascript" src="https://b92.yahoo.co.jp/js/s_retargeting.js"></script>


</body>
</html>