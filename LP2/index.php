<?php
require "common/mail_include/formMail_min.php";
require "common/mail_include/item.php";

$pagekey = 'contact';

if( isset($_GET["p"]) && $_GET["p"]=='back' ) {
	$post = $_SESSION[$pagekey]['post'];
}
elseif($_SERVER["REQUEST_METHOD"]==="POST" ){
		
	$errMsg = '';
	foreach( $ITEMS_ARRAY as $key => $ary ) {
				
		//post値格納
		//---------------------
		switch( $ary['type'] ){
			case 'text':
			case 'tel':
			case 'email':
				$post[$key] = makeNoSpString((string)filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING));
				$post[$key] = mb_convert_kana($post[$key], K, "UTF-8");		//半角カタカナは全角に変換
				break;
				
			case 'textarea':
				$post[$key] = makeNoSpString((string)filter_input(INPUT_POST, $key));
				$post[$key] = mb_convert_kana($post[$key], K, "UTF-8");		//半角カタカナは全角に変換
				break;
				
			case 'select':
			case 'checkbox':
				$post[$key] = filter_var($ary['plural'], FILTER_VALIDATE_BOOLEAN) ? filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY) :(string)filter_input(INPUT_POST, $key);
				break;
				
			case 'radio':
				$post[$key] = (string)filter_input(INPUT_POST, $key);
				break;
				
			default;
				break;
		}
		
		//入力値チェック
		//---------------------
		if( isset($post[$key]) ){
			
			//入力なし
			if( (!is_array($post[$key]) && $post[$key]=='') || (is_array($post[$key]) && $post[$key][0]=='')){
				//必須チェック
				if( $ary['req']=='yes' ){
					$errMsg .= "▼「".$ary['tag']."」を入力してください。<br>";
				}
			}
			//入力あり
			else{
				//適合チェック　input関連のみなので配列は想定外
				switch( $ary['check'] ){
					case 'kana':
						if(!kanaHiragana_check($post[$key]) ){
							$errMsg .= "▼「".$ary['tag']."」は全角カタカナか平仮名で入力してください。<br>";
						}
						break;
						
					case 'email':
						if( !mail_checkk($post[$key]) ){
							$errMsg .= "▼「".$ary['tag']."」が適切ではありません。<br>";
						}
						break;
						
					case 'zip':
						$post[$key] = mb_convert_kana($post[$key], n, "UTF-8");
						$post[$key] = hyphen_changeToHalf($post[$key]);
						if( !zip_checkk($post[$key]) ){
							$errMsg .= "▼「".$ary['tag']."」を正しく入力してください。<br>";
						}
						break;
						
					case 'tel':		//電話番号チェック、tel_checkk2は、ハイフンと数字および+とカッコが入力可
						$post[$key] = mb_convert_kana($post[$key], n, "UTF-8");
						if( !tel_checkk2($post[$key]) ){
							$errMsg .= "▼「".$ary['tag']."」を正しく入力してください。<br>";
						}
						break;
						
					default:
						break;
				}
				
				// 文字数チェック  mb_strlenがインストールされていない場合は*2でチェック
				if( $ary['maxlength']>0 && mb_strlen($post[$key], "UTF-8") > $ary['maxlength']){
					$errMsg .= "▼「".$ary['tag']."」の文字数が多すぎます。<br>";
				}
			}
		}
	}	
		
	// エラーがなければ送信
	if( empty($errMsg) ) {
		$_SESSION[$pagekey]['post'] = $post;
		header("Location:./confirm.php");
		exit();
	}
}else{
	unset($_SESSION[$pagekey]['post']);
}

//HTML形成
foreach( $ITEMS_ARRAY as $key => $ary ) {
	$class = strlen($ary['class'])>0 ? ' class="'.$ary['class'].'"': '';
	$require = filter_var($ary['require'], FILTER_VALIDATE_BOOLEAN) ? ' required' : '';
	$placeholder = strlen($ary['placeholder'])>0 ? ' placeholder="'.$ary['placeholder'].'"' : '';
	
	switch( $ary['type'] ){
		case 'text':
		case 'tel':
		case 'email':
			if( isset($ary['editing']) && ($ary['editing']=='readonly' || $ary['editing']=='disabled') ){
				$editing = ' '.$ary['editing'];
				$str_html[$key] = '<input type="'.$ary['type'].'" name="'.$key.'" value="'.htmlDisplayText($post[$key]).'"'.$editing.$placeholder.$class.$require.'>';
			}else{
				$str_html[$key] = '<input type="'.$ary['type'].'" name="'.$key.'" value="'.htmlDisplayText($post[$key]).'"'.$placeholder.$class.$require.'>';
			}
			break;
		case 'textarea':
			$str_html[$key] = '<textarea name="'.$key.'"'.$require.'>'.htmlDisplayText($post[$key]).'</textarea>';
			break;
		case 'select':
			$str_html[$key] = createComboboxForm($ary['choices'], $key, $post[$key], $class, $require );
			break;
		case 'radio':
			$str_html[$key] = createRadioForm2($ary['choices'], $key, $post[$key], 1, $require, $class );
			break;
		default:
			break;
	}
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
			<p class="btn_cntact"><a href="#contactform"><img src="img/header_btn_contact.png" alt="24時間受付中　無料のご相談"></a></p>
			<p class="tel"><a onclick="goog_report_conversion('tel:0359019633')" href="#"><img src="img/header_tel.png" alt="24時間受付中　無料のご相談"></a></p>
		</div>
	</header>
	
	<div class="frameTop">
		<p class="mainImage"><img src="img/main_image.png" alt="縫製関連の物販販売グッツ、プロモーショングッツを低価格かつ高品質でご提供致します。"></p>
	</div>
	
	<div id="wrapper">
		<p class="banner"><a href="#contactform"><img src="img/banner.jpg" alt="ハンドタオルから抱き枕まで縫製関連の製品をオリジナルフルカラー製作できます！"></a></p>
		
		<article class="box_green">
			<h2><img src="img/title_3reason.png" alt="当社がお客様からあらバレル３つの理由"></h2>
			<div class="section">
				<h3 class="textCenter"><img src="img/subtitle_1.png" alt="昇華転写の印刷技法だからとてもキレイな仕上がり"></h3>
				<ul class="list_reason">
					<li><img src="img/reason1.png" alt=""></li>
					<li><img src="img/reason2.png" alt=""></li>
					<li><img src="img/reason3.png" alt=""></li>
				</ul>
			</div>
		</article>
		
		<p class="banner"><a href="#contactform"><img src="img/banner.jpg" alt="ハンドタオルから抱き枕まで縫製関連の製品をオリジナルフルカラー製作できます！"></a></p>
		
		<article class="box_green">
			<h2><img src="img/title_original.png" alt="オリジナル縫製製品一覧"></h2>
			<div class="sectionItem">
			
				<div class="frameItem" id="blanket1">
					<div class="txt">
						<p class="photo"><img src="img/blanket_1/item.png" alt=""></p>
						<h3>ブランケット<span>起毛タイプ</span></h3>
						<p class="cmt">ポリエステル起毛300ℊのふわふわあったかタイプ</p>
						<dl>
							<dt>素材</dt>
							<dd>ポリエステル起毛300g</dd>
							<dt>加工</dt>
							<dd>四方縫い</dd>
							<dt>印刷</dt>
							<dd>表面昇華転写フルカラー</dd>
							<dt>包装</dt>
							<dd>個別ＯＰＰ袋入れ</dd>
							<dt>価格</dt>
							<dd></dd>
						</dl>
						<p class="box_sample"><img src="img/blanket_1/box_sample.png" alt=""></p>
					</div>
					<div class="price">
						<img src="img/blanket_1/table_size.png" alt="">
						<p>
							<a href="pdf/blanket_kimou500.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/blanket_kimou700.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
						</p>
					</div>
				</div>
				
				<div class="frameItem" id="blanket2">
					<div class="txt">
						<p class="photo"><img src="img/blanket_2/item.png" alt=""></p>
						<h3>ブランケット<span>2枚合わせタイプ</span></h3>
						<p class="cmt">表面はベネシャン生地にフルカラー印刷、<br>裏面はあったかいフリース生地の2枚あわせタイプ</p>
						<dl>
							<dt>素材</dt>
							<dd>表／ベネシャン　裏／フリース</dd>
							<dt>加工</dt>
							<dd>周囲パイピング</dd>
							<dt>印刷</dt>
							<dd>表面昇華転写フルカラー</dd>
							<dt>包装</dt>
							<dd>個別ＯＰＰ袋入れ</dd>
							<dt>価格</dt>
							<dd></dd>
						</dl>
						<p class="box_sample"><img src="img/blanket_2/box_sample.png" alt=""></p>
					</div>
					<div class="price">
						<img src="img/blanket_2/table_size.png" alt="">
						<p>
							<a href="pdf/blanket500.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/blanket700.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
						</p>
					</div>
						
					<h4>■ パイピング色　（パイピングの色は、下記よりお選びください。）</h4>
					<img src="img/blanket_2/color_variation_1.png" alt="1.レッド, 2.スカイブルー, 3.オレンジ, 4.イエロー, 5.ピンク, 6.グレー, 7.ブラック, 8.グリーン, 9.ブラウン, 10.ホワイト, 11.ロイヤルブルー, 12.エンジ">
					<h4>■ 裏生地フリースの色　（ブランケット裏生地／フリースの色は、下記よりお選びください。）</h4>
					<img src="img/blanket_2/color_variation_2.png" alt="1.レッド, 2.ピンク, 3.ライトピンク, 4.オレンジ, 5.イエロー, 6.ホワイト, 7.グリーン, 8.スカイブルー, 9.ライトブラウン, 10.ブラウン, 11.グレー, 12.ブラック">
				</div>
			</div>
			
			<div class="sectionItem">
				<div class="frameItem" id="towel">
					<div class="txt">
						<p class="photo"><img src="img/towel/item.png" alt=""></p>
						<h3 class="pt30">タオル<span>（ポリエステル・ナイロン製）</span></h3>
						<p class="cmt">吸水性・速乾性が強いポリエステルナイロン製、<br>手触りが心地よいウォッシュタオル</p>
						<dl>
							<dt>素材</dt>
							<dd>ポリエステル、ナイロン</dd>
							<dt>加工</dt>
							<dd>周囲ロック</dd>
							<dt>印刷</dt>
							<dd>片面昇華転写フルカラー</dd>
							<dt>包装</dt>
							<dd>個別ＯＰＰ袋入れ</dd>
							<dt>価格</dt>
							<dd></dd>
						</dl>
						<p class="box_sample"><img src="img/towel/box_sample.png" alt=""></p>
					</div>
					<div class="price">
						<img src="img/towel/table_size.png" alt="">
						<p>
							<a href="pdf/towel_hand.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/towel_muffler.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/towel_face.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/towel_bath.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
						</p>
					</div>
						
					<h4>■ ロック色　（周囲ロックの色は、下記よりお選びください。）</h4>
					<img src="img/towel/color_variation.png" alt="1.オレンジ, 2.グリーン, 3.ブラウン, 4.エンジ, 5.レッド, 6.ブラック, 7.ホワイト, 8.ピンク, 9.クリーム, 10.イエロー, 11.スカイブルー, 12.ロイヤルブルー, 13.パープル">
				</div>
			</div>
			
			<div class="sectionItem">
				<div class="frameItem" id="tote">
					<div class="txt">
						<p class="photo"><img src="img/tote/item.png" alt=""></p>
						<h3 class="pt30">トートバック</h3>
						<p class="cmt">トートバック全面に余白なくフルカラー印刷できます</p>
						<dl>
							<dt>素材</dt>
							<dd>ポリエステル600d</dd>
							<dt>印刷</dt>
							<dd>昇華転写フルカラー</dd>
							<dt>包装</dt>
							<dd>個別ＯＰＰ袋入れ</dd>
							<dt>価格</dt>
							<dd></dd>
						</dl>
						<p class="box_sample"><img src="img/tote/box_sample.png" alt=""></p>
					</div>
					<div class="price">
						<img src="img/tote/table_size.png" alt="">
						<p>
							<a href="pdf/totebag250.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/totebag300-440.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/totebag300-570.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/totebag400.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/totebag460.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
						</p>
					</div>
				</div>
			</div>
			
			<div class="sectionItem">
				<div class="frameItem" id="tapestry">
					<div class="txt">
						<p class="photo"><img src="img/tapestry/item.png" alt=""></p>
						<h3 class="pt30">タペストリー</h3>
						<p class="cmt">アニメキャラクター物販・展示会等での人気定番商品タペストリー</p>
						<h4>タペストリー①</h4>
						<dl>
							<dt>素材</dt>
							<dd>スエード</dd>
							<dt>加工</dt>
							<dd>上下／パイプ付き　上部／紐付き</dd>
							<dt>印刷</dt>
							<dd>片面昇華転写フルカラー</dd>
							<dt>包装</dt>
							<dd>個別ＯＰＰ袋入れ</dd>
							<dt>価格</dt>
							<dd></dd>
						</dl>
						<p class="box_sample"><img src="img/tapestry/box_sample.png" alt=""></p>
					</div>
					<div class="price">
						<img src="img/tapestry/table_size.png" alt="">
						<p>
							<a href="pdf/tapestry_tateB2.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							<a href="pdf/tapestry_yokoB2.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
						</p>
					</div>
					
					<div id="tapestry2">
						<div class="txt">
							<h4>タペストリー②</h4>
							<dl>
								<dt>素材</dt>
								<dd>スエード</dd>
								<dt>加工</dt>
								<dd>上下／パイプ付き　上部／紐付き</dd>
								<dt>印刷</dt>
								<dd>片面昇華転写フルカラー</dd>
								<dt>包装</dt>
								<dd>個別ＯＰＰ袋入れ</dd>
								<dt>価格</dt>
								<dd></dd>
							</dl>
							<p class="box_sample"><img src="img/tapestry/box_sample.png" alt=""></p>
						</div>
						<div class="price">
							<img src="img/tapestry/table_size2.png" alt="">
							<p>
								<a href="pdf/tapestry_tateB2.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
								<a href="pdf/tapestry_yokoB2.pdf" target="_blank"><img src="img/btn_template.png" alt="テンプレートはこちら"></a>
							</p>
						</div>
					</div>
				</div>
			</div>
			
			<div class="sectionItem">
				<div class="frameItem" id="cushion">
					<div class="txt">
						<p class="photo"><img src="img/cushion/item.png" alt=""></p>
						<h3 class="pt30">クッション</h3>
						<p class="cmt">お部屋のお供の必需品、オリジナルフルカラークッション</p>
						<dl>
							<dt>素材</dt>
							<dd>表／ベネシャン　中／綿</dd>
							<dt>加工</dt>
							<dd>圧縮加工</dd>
							<dt>印刷</dt>
							<dd>表面昇華転写フルカラー</dd>
							<dt>包装</dt>
							<dd>個別圧縮用ＯＰＰ袋入れ</dd>
							<dt>価格</dt>
							<dd></dd>
						</dl>
						<p class="box_sample"><img src="img/cushion/box_sample.png" alt=""></p>
					</div>
						<img src="img/cushion/table_size.png" alt="">
				</div>
			</div>
			
			<div class="sectionItem">
				<div class="frameItem" id="pillow">
					<div class="txt">
						<p class="photo"><img src="img/pillow/item.png" alt="" style="margin-left:70px;"></p>
						<h3 class="pt30">抱き枕</h3>
						<p class="cmt">お部屋のお供の必需品、オリジナルフルカラークッション</p>
						<dl>
							<dt>素材</dt>
							<dd>トリコット</dd>
							<dt>加工</dt>
							<dd>短編一箇所ファスナー付き</dd>
							<dt>印刷</dt>
							<dd>表面昇華転写フルカラー</dd>
							<dt>包装</dt>
							<dd>個別ＯＰＰ袋入れ</dd>
							<dt>価格</dt>
							<dd></dd>
						</dl>
						<p class="box_sample"><img src="img/pillow/box_sample.png" alt=""></p>
					</div>
						<img src="img/pillow/table_size.png" alt="">
				</div>
			</div>
			
			<div class="sectionItem">
				<div class="frameItem" id="accessory">
					<div class="txt">
						<h3 class="pt30">小物</h3>
						<p class="cmt">その他、縫製・フルカラー商品の製造はお気軽にお問合せください！</p>
						<dl>
							<dt>印刷</dt>
							<dd>表面昇華転写フルカラー</dd>
							<dt>価格</dt>
							<dd></dd>
						</dl>
						<p class="box_sample"><img src="img/accessory/box_sample.png" alt=""></p>
					</div>
						<img src="img/accessory/table_size.png" alt="">
				</div>
			</div>
			<p class="note">データ作成・デザイン作成もお気軽にお問合せください！</p>
		</article>
		
		<p class="banner"><a href=""><img src="img/banner.jpg" alt="ハンドタオルから抱き枕まで縫製関連の製品をオリジナルフルカラー製作できます！"></a></p>
		
		<article>
			<h2><img src="img/title_voice.png" alt="お客様の声"></h2>
			<div id="frameVoice">
				<img src="img/voice/A.png" alt="" id="voice_A">
				<img src="img/voice/B.png" alt="" id="voice_B">
				<img src="img/voice/C.png" alt="" id="voice_C">
			</div>
		</article>
		
		<article>
			<h2><img src="img/title_flow.png" alt="納品までの流れ"></h2>
			<div id="frameFlow">
				<ul>
					<li>
						<h3>お問い合わせ・打ち合わせ</h3>
						<p>用途や素材、原料などのご要望を伺い、営業担当者と技術者が基本の仕様を確認します。</p>
					</li>
					<li>
						<h3>お見積り</h3>
						<p>基本の仕様をお聞きし、そこからお客様に最適なお見積もりをご提案いたします。</p>
					</li>
					<li>
						<h3>ご発注</h3>
						<p>お見積もりをご確認いただき、問題なければご発注いただきます。</p>
					</li>
					<li>
						<h3>製品化</h3>
						<p>試作品をご確認後、製品として製作いたします。</p>
					</li>
					<li>
						<h3>納品</h3>
						<p>ご指定の納期にあわせて確実に納品いたします。</p>
					</li>
				</ul>
			</div>
		</article>
		
		<article class="box_plad">
			<div id="frameFaq">
				<h2><img src="img/title_faq.png" alt="よくあるご質問"></h2>
				<ul>
					<li>
						<p class="qes">サンプル・試作の製造は可能でしょうか？</p>
						<p class="ans">はい、可能です。基本的にはサンプルをご了承後の製造をお勧めいたします。</p>
					</li>
					<li>
						<p class="qes">ＨＰに記載がない商品でも可能でしょうか？</p>
						<p class="ans">商品仕様にもよりますが、可能です。お気軽にお問合せください。</p>
					</li>
					<li>
						<p class="qes">生産国はどこですか？</p>
						<p class="ans">中国製になります。中国から船便での輸送になります。<br>（日本製をご要望の場合はお気軽にお問合せください。）</p>
					</li>
					<li>
						<p class="qes">データ作成やデザインも可能でしょうか？</p>
						<p class="ans">はい、可能です。データ入稿の調整・デザイン等もお気軽にお問合せください。</p>
					</li>
				</ul>
			</div>
		</article>
		
		<article class="box_red" id="contactform">
			<div id="frameContact">
				<h2><img src="img/title_contact.png" alt="お問合せフォーム"></h2>
				<form name="contact_form" action="index.php" method="post">
					<dl>
<?php echo '<dd class="error_msg">'.$errMsg."</dd>\n";?>
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
						<input type="submit" value="内容を確認する" class="btn_m">
					</p>
				</form>
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
					<td>〒114-0012 東京都北区田端新町1-7-8 相光ビル3階</td>
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
	
	<!-- Google Code for tel Conversion Page
	In your html page, add the snippet and call
	goog_report_conversion when someone clicks on the
	phone number link or button. -->
	<script type="text/javascript">
	  /* <![CDATA[ */
	  goog_snippet_vars = function() {
		var w = window;
		w.google_conversion_id = 820854538;
		w.google_conversion_label = "cAEICPjZ1HsQiv60hwM";
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
	}
	/* ]]> */
	</script>
	<script type="text/javascript"
	  src="//www.googleadservices.com/pagead/conversion_async.js">
	</script>
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
