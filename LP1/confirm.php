<?php session_start();
$_SESSION['lc'] = 'yes';
$namemb = $_POST['name'];
$emailmb = $_POST['email'];
$phonemb = $_POST['phone'];
$inqmb = $_POST['inq'];
$name = mb_convert_kana($namemb,'KVRN','UTF-8');
$email = mb_convert_kana($emailmb,'kvrn','UTF-8');
$phone = mb_convert_kana($phonemb,'kvrn','UTF-8');
$inq = mb_convert_kana($inqmb,'KVRN','UTF-8');
?>
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


</head>
<body>
<form method="post" action="thanks.php">
<div class="form">
  <div class="form_inner">
    <p>ただいまお問い合わせを多数頂いておりご相談いただくタイミングによっては<br />
      少しお時間を頂戴してしまう可能性がございます。<br />
      ただし、お問い合わせをいただけましたら必ず24時間以内にはご返信をいたします。</p>
    <h2><img src="./images/ttl_form.png"></h2>
    <div class="bg_form_inner">
      <dl>
        <dt>お名前<span>必須</span></dt>
        <dd> 
         <?php if($name){
           echo $name;
         }else{
         echo '<span>必須項目です。入力してください</span>'; $error= 'error';
         }?>
        </dd>
         <input size="20" type="hidden" name="name" value ="<?php echo $name;?>"/>
      </dl>
      <dl>
        <dt>メールアドレス<span>必須</span></dt>
        <dd> 
        <?php if($email){
          if (preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/",$email)){
           echo $email;
          }else{
           echo '<span>不当なメールアドレスです。</span>';
           $error= 'error';
          }
        }else{
          echo '<span>必須項目です。入力してください</span>'; $error= 'error';
        }?>
        
        </dd>
         <input size="20" type="hidden" name="email" value ="<?php echo $email;?>"/>
      </dl>
      <dl>
        <dt>お電話番号<span>必須</span></dt>
        <dd><?php if($phone){
         if (preg_match("/^[0-9\-]+$/",$phone)){
          echo $phone;
         }else{
         echo '<span>無効な電話番号です</span>'; $error= 'error';
         }
         
        }else{
        echo '<span>必須項目です。入力してください</span>'; $error= 'error';}?>
        </dd>
        <input size="20" type="hidden" name="phone" value ="<?php echo $phone;?>"/>
      </dl>
      <dl>
        <dt>お問い合わせ内容<span>必須(500文字まで)</span></dt>
        <dd style="whitespace:normal;"><?php if($inq){
           $count = mb_strlen($inq);
           if($count > 500){
            echo '<span>文字数オーバーです。</span>'; $error= 'error';
           }else{
            echo $inq;
           }
          
         }else{
          echo '<span>必須項目です。入力してください</span>'; $error= 'error';
         }?>
        </dd>
        <input size="20" type="hidden" name="inq" value ="<?php echo $inq;?>"/>
      </dl>
    <?php if($error):?>
    <p style="color:red;">エラーの項目があります。</p>
    <p><input id="hisback" type="button" value="" onClick="history.back()"></p>
    <?php else:?>
    <input id="confirmimg" type="image" src="./images/btn_done.jpg" value="hoge" name="submit" style="border:0; width:356px; height:76px; padding:0; margin:24px auto 0; display:table;"/>
    <img id="hisbackimg" src="./images/btn_back.jpg" onClick="history.back()" style="border:0; margin:48px auto 24px; width:204px; height:44px; display:table; padding:0;">
    <?php endif;?>
    <style>
    input#hisback{border:0px; width:204px; height:44px; background: url(./images/btn_back.jpg) left top no-repeat; background-size:contain;}
    input#hisback:hover,#hisbackimg:hover,#confirmimg:hover{opacity:0.7; cursor:pointer;}
    input#send{border:0px; width:356px; height:76px; background: url(./images/btn_done.jpg) left top no-repeat; background-size:contain;}
    input#hisback:hover{opacity:0.7; cursor:pointer;}
    </style>
    </div>
  </div>
</div>
</form>
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