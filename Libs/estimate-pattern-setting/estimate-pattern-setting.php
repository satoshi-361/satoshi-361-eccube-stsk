<?php
/*
 * Plugin Name: 印刷方法別単価設定プラグイン
 * Plugin URI:
 * Description: 印刷方法別単価の設定
 * Version: 1.0.0
 * Author: AIOSL technology
 * Author URI:
 * License: aiosl
 */
add_action ( 'init', 'EstimatePatternSetting::init' );
add_shortcode( 'estimatePatternTag', [ 'EstimatePatternSetting', 'aiosl_shortcode' ] );
add_shortcode( 'estimatePatternTagForBtn', [ 'EstimatePatternSetting', 'aiosl_shortcode_for_btn' ] );

class EstimatePatternSetting {
	const VERSION = '1.0.0';
	const PLUGIN_ID = 'estimate-pattern-setting';
	const CREDENTIAL_ACTION = self::PLUGIN_ID . '-nonce-action';
	const CREDENTIAL_NAME = self::PLUGIN_ID . '-nonce-key';
	const PLUGIN_DB_PREFIX = self::PLUGIN_ID . '_';
	const COMPLETE_CONFIG = '';
	static function init() {
		return new self ();
	}
	function __construct() {
		if (is_admin () && is_user_logged_in ()) {
			// メニュー追加
			add_action ( 'admin_menu', [ $this, 'aiosl_set_plugin_menu' ] );
			add_action ( 'admin_init', [ $this, 'aiosl_save_config' ] );
		}
	}

	/**
	 * サイドメニュー表示
	 */
	function aiosl_set_plugin_menu() {
		add_menu_page ( '印刷方法別単価設定', '印刷方法別単価設定', 'manage_options', 'estimate-pattern-setting', [ $this,'aiosl_show_plugin' ], 'dashicons-buddicons-activity', 99 );
	}

	/**
	 * 保存（wp_options）
	 */
	function aiosl_save_config() {
		if (isset ( $_POST [self::CREDENTIAL_NAME] ) && $_POST [self::CREDENTIAL_NAME]) {
			if (check_admin_referer ( self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME )) {
				/* 入力チェック */
				$updateDatas = $_POST['datas'];
				$isValidate = true;
				foreach($updateDatas as $data) {
					// 版代、データ配置代の値が数値かどうか
					if(!empty($data['data_placement_fee']) && !preg_match('/^(?!0)\d+/', $data['data_placement_fee'])) {
						$isValidate = false;
						$urlPram = 2;
						break;
					}
					// 印刷代の値が数値かどうか
					if(!empty($data['printing_fee']) && !preg_match('/^(?!0)\d+/', $data['printing_fee'])) {
						$isValidate = false;
						$urlPram = 2;
						break;
					}
					// カスタム印刷代
					if (empty($data['printing_fee_custom'])) {
						continue;
					}
					$tmpNum = 0;
					foreach($data['printing_fee_custom'] as $key => $val) {
						// 指定個数（列名）の値が数値かどうか
						if(!preg_match('/^(?!0)\d+/', $key)) {
							$isValidate = false;
							$urlPram = 2;
							break 2;
						}
						// 指定個数の値が左から順に整列されているか
						if($key < $tmpNum) {
							$isValidate = false;
							$urlPram = 3;
							break 2;
						}
						// 各項目の値が数値かどうか
						if(!empty($val) && !preg_match('/^(?!0)\d+/', $val)) {
							$isValidate = false;
							$urlPram = 2;
							break 2;
						}
						$tmpNum = $key;
					}
				}
				if($isValidate) {
					// 保存処理
					$key = 'print-case';
					update_option ( self::PLUGIN_DB_PREFIX . $key, $updateDatas );
					$urlPram = 1;
				}
				// 設定画面にリダイレクト
				$url = admin_url() . '/admin.php?page=estimate-pattern-setting&s=' . $urlPram;
				wp_safe_redirect ( $url, 302 );
			}
		}
	}

	/**
	 * ショートコード
	 */
	public static function aiosl_shortcode() {
		/* 準備 */
		global $post;
		$colorKeys = get_post_meta($post->ID, 'colorsrc', true);
		$colorDetails = get_post(12);
		$colorDatas = unserialize($colorDetails->post_content);
		$colors = [];
		foreach($colorKeys as $val) {
			$colors[$val] = $colorDatas['choices'][$val];
		}
		$printTypeKeys = get_post_meta($post->ID, 'print', true);
		$printTypeDetails = get_post(177);
		$printTypeDatas = unserialize($printTypeDetails->post_content);
		$unitPrices = get_option('estimate-pattern-setting_print-case');
		$printTypes = [];
		foreach($printTypeKeys as $val) {
			$printTypes[$val] = [
				'name' => $printTypeDatas['choices'][$val],
				'data_placement_fee' => $unitPrices[$val]['data_placement_fee'],
				'printing_fee' => $unitPrices[$val]['printing_fee'],
				'printing_custom_fee' => $unitPrices[$val]['printing_fee_custom'],
			];
		}

		$userName = $_SESSION['usces_member']['name1'] . ' ' . $_SESSION['usces_member']['name1'];
		
		/* html */
		$html = '';
		$html .= '
		<style>
			.aiosl_div{ width: 100%; margin-top: 50px; text-align: center; }
			.aiosl_title{ font-size: x-large; }
			.aiosl_e_color{ background: #ededed; padding: 10px; }
			.aiosl_e_color label{ font-size: small; }
			.aiosl_e_ul{ display: flex; flex-wrap: wrap; }
			.aiosl_e_ul li{ width: 50%; display: flex; box-sizing: border-box; justify-content: flex-end; }
			.aiosl_quantity{ width: 50px; margin: 5px; border: 1px gray solid; padding: 3px; }
			.aiosl_e_res{ border: 1px #ededed solid; margin-top: 10px; margin-bottom: 10px; padding: 10px 20px; }
			.total_quantity{ border-top: 1px dashed gray; margin-top: 10px; padding-top: 10px; font-weight: bold; }
			#aiosl_total_num{ padding: 0px 10px; font-size: x-large; }
			#aiosl_err_msg{	position:relative; width:90%; height:7%; background:red; margin: 10px; padding:10px; text-align:center;	color: white; font-size:20px; font-weight:bold;	border-radius:10px;	-webkit-border-radius:10px;	-moz-border-radius:10px; }
			#aiosl_err_msg:after{ border: solid transparent; content:""; height:0; width:0;	pointer-events:none; position:absolute;	border-color: rgba(0, 153, 255, 0);	border-top-width:10px; border-bottom-width:10px; border-left-width:10px; border-right-width:10px; margin-left: -10px; border-bottom-color:red; bottom:100%;	left:50%; }
			.aiosl_calc_btn{ color: white; width: 100%; height: 70px; background: #aacf53; border: none; font-size: large; font-weight: bold;  margin-top: 20px; cursor: pointer; }
			.aiosl_calc_btn:active{ background: #82ae46; border: none; }
			#aiosl_print_case{ margin-left: 20px; background-color: white; }
			.aiosl_idx{ font-weight: bold; }
			.aiosl_e_res dl { position: relative; padding-bottom: 15px; }
			.aiosl_e_res dl .aiosl_e_res_div dd:last-child{ padding-bottom:10px; border-bottom: 1px dashed lightgray; }
			.aiosl_e_res dt { position: absolute; left: 0; padding: 10px 0; }
			.aiosl_e_res dd { padding: 5px 0 5px 60px; text-align: right; color: gray; }
			.aiosl_e_res dd span { display: inline-block; vertical-align: top; }
			.aiosl_e_res dd span:last-child { color: black; padding-left: 10px; }
			#aiosl_total{ font-size: xx-large; }
			#aiosl_pdf_dl{ visibility: hidden; background: skyblue !important; }
			#aiosl_order{ visibility: hidden; background: orange !important; }
			#aiosl_pdf_dl_btn{ width: 100%; padding: 20px 0; font-weight: bold; background: skyblue; color: white; border: none; cursor: pointer; }
			#aiosl_order_btn{ width: 100%; padding: 20px 0; font-weight: bold; background: orange; color: white; border: none; cursor: pointer; }
			.aiosl_breakdown{ font-size: small; vertical-align: top; }
		</style>';
		$html .= '<div class="aiosl_div" id="aiosl_estimate">';
		$html .= '<span class="aiosl_title"> ≪ 自動見積り ≫ </span>';
		$html .= '<div class="aiosl_e_color">';
		$html .= '<p class="aiosl_idx">本体色</p>';
		$html .= '<ul class="aiosl_e_ul">';
		$colorCnt = count($colors);
		foreach ($colors as $key => $color) {
			$html .= '<li>';
			$html .= '<label>';
			$html .= '<span>' . $color . '</span>';
			$html .= '<input type="number" class="aiosl_quantity" name="quantity[' . $key . ']" value="" maxlength="9"><i>個</i>';
			$html .= '</label>';
			$html .= '</li>';
		}
		if($colorCnt < 10) {
			$cnt = 10 - $colorCnt;
			for($i = 0; $i < $cnt ; $i++) {
				$html .= '<li style="visibility: hidden;">';
				$html .= '<label>';
				$html .= '<span></span>';
				$html .= '<input type="number" class="aiosl_quantity" name="" value="" maxlength="9">';
				$html .= '</label>';
				$html .= '</li>';				
			}
		}
		$html .= '</ul>';
		$html .= '<p class="total_quantity">合計部数<span id="aiosl_total_num">0</span>個</p>';
		$html .= '<span class="aiosl_idx">印刷方法</span>';
		$html .= '<select id="aiosl_print_case">';
		$html .= '<option value="">印刷なし</option>';
		foreach ($printTypes as $key => $data){
			if (empty($data['data_placement_fee']) || empty($data['printing_fee']) || empty($data['name'])){
				continue;
			}
			$name = preg_split('/[\s|\x{3000}]+/u', $data['name']);
			$html .= '<option value="' . $key . '">' . $name[0] . '</option>';
		}
		$html .= '</select>';
		$html .= '<br>';
		$html .= '<div id="aiosl_err_msg" style="display:none;"></div>';
		$html .= '<button class="aiosl_calc_btn">計算する</button>';
		$html .= '</div>';
				
		$html .= '
			<div class="aiosl_e_res">
				<dl>
					<p class="aiosl_idx">見積金額（概算）</p>
					<div class="aiosl_e_res_div">
						<dt>商品</dt>
						<dd>
							<span id="aiosl_product_fee_breakdown" class="aiosl_breakdown">---</span>
							<span id="aiosl_product_fee">---</span>
						</dd>
					</div>
					<div class="aiosl_e_res_div">
						<dt>版代</dt>
						<dd>
							<span id="aiosl_edition_fee_breakdown" class="aiosl_breakdown">---</span>
							<span id="aiosl_edition_fee">---</span>
						</dd>
					</div>
					<div class="aiosl_e_res_div">
						<dt>印刷代</dt>
						<dd>
							<span id="aiosl_print_fee_breakdown" class="aiosl_breakdown">---</span>
							<span id="aiosl_print_fee">---</span>
						</dd>
					</div>
					<div class="aiosl_e_res_div">
						<dt>送料</dt>
						<dd>
							<span id="aiosl_shipment_fee_breakdown" class="aiosl_breakdown">---</span>
							<span id="aiosl_shipment_fee">---</span>
						</dd>
					</div>
					<div class="aiosl_e_res_div">
						<dt>税抜合計</dt>
						<dd>
							<span id="aiosl_total_excluding_tax">---</span>
						</dd>
					</div>
					<div class="aiosl_e_res_div">
						<dt>消費税</dt>
						<dd>
							<span id="aiosl_tax">---</span>
						</dd>
					</div>
					<div class="">
						<dt>税込合計</dt>
						<dd>
							<span id="aiosl_total">---</span>
						</dd>
					</div>
				</dl>
			</div>
		</div>

		<script>
		const arr = '. json_encode($printTypes) .';
		/* 合計数表示 */
		document.querySelectorAll("#aiosl_estimate .aiosl_quantity").forEach(function(inputBox) {
			inputBox.addEventListener("change", function() {
				let total = 0;
				document.querySelectorAll("#aiosl_estimate .aiosl_quantity").forEach(function(elm) {
					if(elm.value == "") {
						return;
					}
					total += parseInt(elm.value, 10);
				});
				document.getElementById("aiosl_total_num").textContent = total;
				document.querySelectorAll(".aiosl_e_res span").forEach(function(elm) {
					elm.textContent = "---";
				});
				document.getElementById("aiosl_pdf_dl").style.visibility = "hidden";
				document.getElementById("aiosl_pdf_dl_btn").style.visibility = "hidden";
				document.getElementById("aiosl_order").style.visibility = "hidden";
				document.getElementById("aiosl_order_btn").style.visibility = "hidden";
			});
		}, false);

		document.querySelector("#aiosl_print_case").addEventListener("change", function() {
			document.querySelectorAll(".aiosl_e_res span").forEach(function(elm) {
				elm.textContent = "---";
			});
			document.getElementById("aiosl_pdf_dl").style.visibility = "hidden";
			document.getElementById("aiosl_pdf_dl_btn").style.visibility = "hidden";
			document.getElementById("aiosl_order").style.visibility = "hidden";
			document.getElementById("aiosl_order_btn").style.visibility = "hidden";
		});

		/* 見積計算 */
		document.querySelector(".aiosl_calc_btn").addEventListener("click", function() {
			// 準備
			var total = parseInt(document.querySelector("#aiosl_total_num").textContent, 10);
			if(!inputValidation()) {
				return false;
			}
			if (total == 0) {
				return;
			}
			var type = document.getElementById("aiosl_print_case").value;
			var price = (document.querySelector(".pricebox span").textContent).replace(/,/g, "");
			price = parseInt(price, 10);
			var printingFee = 0;
			var dataPlacementFee = 0;
			var tax = 0;
			var shipmentFee = 0;
			var printingFeeQuantity = 1;
			// 計算
			if (type) {
				printingFee = parseInt(arr[type]["printing_fee"], 10);
				dataPlacementFee = parseInt(arr[type]["data_placement_fee"], 10);
				var beforeKey = 9999999999999;
				var printFeeBreakdown = "１式　×　" + printingFee + " 円";
				if (arr[type]["printing_custom_fee"]) {
					Object.keys(arr[type]["printing_custom_fee"]).reverse().forEach(function (key) {
						if(arr[type]["printing_custom_fee"][key] == "") {
							return;
						}
						key = parseInt(key, 10);
						if(beforeKey > total && total >= key) {
							printFeeBreakdown = total + " 個　×　" + parseInt(arr[type]["printing_custom_fee"][key], 10) + " 円";
							printingFeeQuantity = total;
							printingFee = parseInt(arr[type]["printing_custom_fee"][key], 10);
						}
						beforeKey = key;
					});
				}
				document.getElementById("aiosl_edition_fee").textContent = dataPlacementFee + " 円";
				document.getElementById("aiosl_edition_fee_breakdown").textContent = "１版　×　" + dataPlacementFee + " 円";
				document.getElementById("aiosl_print_fee").textContent = (printingFee * printingFeeQuantity) + " 円";
				document.getElementById("aiosl_print_fee_breakdown").textContent = printFeeBreakdown;
			}
			shipmentFee = ((price * total) + dataPlacementFee + (printingFee * printingFeeQuantity) ) < 35000 ? 1000 : 0;
			document.getElementById("aiosl_shipment_fee").textContent = shipmentFee + " 円";;
			document.getElementById("aiosl_shipment_fee_breakdown").textContent = "";
			var zeinukiTotal = (price * total) + dataPlacementFee + (printingFee * printingFeeQuantity) + shipmentFee;
			document.getElementById("aiosl_total_excluding_tax").textContent = zeinukiTotal + " 円";
			document.getElementById("aiosl_product_fee").textContent = (price * total) + " 円";
			document.getElementById("aiosl_product_fee_breakdown").textContent = total + " 個　×　" + price + " 円";
			tax = Math.round(((price * total) + dataPlacementFee + (printingFee * printingFeeQuantity) + shipmentFee) * 0.1);
			document.getElementById("aiosl_tax").textContent = tax + " 円";
			var totalAmount = zeinukiTotal + tax;
			document.getElementById("aiosl_total").textContent = totalAmount.toLocaleString() + " 円";
			document.getElementById("aiosl_pdf_dl").style.visibility = "visible";
			document.getElementById("aiosl_pdf_dl_btn").style.visibility = "visible";
			document.getElementById("aiosl_order").style.visibility = "visible";
			document.getElementById("aiosl_order_btn").style.visibility = "visible";
			// hiddenに値代入
			document.getElementById("aioslProductName").value = document.querySelector(".lv_h1 span").textContent;
			document.getElementById("aioslProductPrice").value = price;
			document.getElementById("aioslTotalQuantity").value = total;
			document.getElementById("aioslPrintingFee").value = printingFee;
			document.getElementById("aioslPrintingFeeQuantity").value = printingFeeQuantity;
			document.getElementById("aioslDataPlacementFee").value = dataPlacementFee;
			document.getElementById("aioslShipmentFee").value = shipmentFee;
			document.getElementById("aioslZeinuki").value = zeinukiTotal;
			document.getElementById("aioslTax").value = tax;
			document.getElementById("aioslTotalAmount").value = totalAmount;
			document.getElementById("aioslUserName").value = "'. $userName .'";
		}, false);

		function inputValidation(){
			var isValid = true;
			var nums = document.querySelectorAll(".aiosl_quantity");
			var total = 0;
			nums.forEach(function(elm) {
				total += elm.value;
				if(elm.value < 0 || elm.value == "-0" ) {
					elm.setCustomValidity("個数は1以上となるように入力してください。");
					elm.checkValidity();
					elm.reportValidity();
					isValid = false;
				}
			});
			document.getElementById("aiosl_err_msg").innerHTML = "";
			document.getElementById("aiosl_err_msg").style.display = "none";
			if(total < 30) {
				document.getElementById("aiosl_err_msg").innerHTML = "30個以上からお見積りが可能です。";
				document.getElementById("aiosl_err_msg").style.display = "block";
				isValid = false;
			}
			return isValid;
		}
		</script>';
		
		return $html;
	}
	
	/**
	 * ショートコード（ボタン）
	 */
	public static function aiosl_shortcode_for_btn() {
		$action = plugins_url() . '/estimate-pattern-setting';
		$js = '';
		$js = '
			<script>
				function _btnClickAiosl(action){
					var actionUrl = "";
					if (action == 1) {
						actionUrl = "' . $action . '/download-pdf.php";
					} else if (action == 2) {
						actionUrl = "' . $action . '/template/order.php";
						_getColor();
					}
					document.estimate_form.action = actionUrl;
					document.estimate_form.submit();
				}

				function _getColor(){
					var nums = document.querySelectorAll(".aiosl_quantity");
					const orderForm = document.getElementById("estimate_form");
					nums.forEach(function(elm) {
						if(elm.value > 0) {
							const inpHidden = document.createElement("input");
							inpHidden.setAttribute("type", "hidden");
							inpHidden.setAttribute("name", elm.name);
							inpHidden.setAttribute("value", elm.value);
							orderForm.appendChild(inpHidden);
						}
					});
				}
			</script>';
		$html = '';
		$html .='
			<li id="aiosl_pdf_dl">
				<button type="button" id="aiosl_pdf_dl_btn" onclick="_btnClickAiosl(1)">見積書ダウンロード</button>
			</li>
			<li id="aiosl_order">
				<button type="button" id="aiosl_order_btn" onclick="_btnClickAiosl(2)">注文する</button>
			</li>
			<form id="estimate_form" name="estimate_form" action="" method="post">
				<input type="hidden" name="IsOrderFlg" value="1">
				<input type="hidden" id="aioslProductName" name="order[productName]" value="" >
				<input type="hidden" id="aioslProductPrice" name="order[productPrice]" value="" >
				<input type="hidden" id="aioslTotalQuantity" name="order[totalQuantity]" value="" >
				<input type="hidden" id="aioslPrintingFee" name="order[printingFee]" value="" >
				<input type="hidden" id="aioslPrintingFeeQuantity" name="order[printingFeeQuantity]" value="" >
				<input type="hidden" id="aioslDataPlacementFee" name="order[dataPlacementFee]" value="" >
				<input type="hidden" id="aioslShipmentFee" name="order[shipmentFee]" value="" >
				<input type="hidden" id="aioslZeinuki" name="order[zeinuki]" value="" >
				<input type="hidden" id="aioslTax" name="order[tax]" value="" >
				<input type="hidden" id="aioslTotalAmount" name="order[totalAmount]" value="" >
				<input type="hidden" id="aioslUserName" name="order[userName]" value="" >
			</form>
		';
		return $js.$html;
	}
	
	/**
	 * 印刷方法別単価設定画面
	 */
	function aiosl_show_plugin() {
		$title = get_option ( self::PLUGIN_DB_PREFIX . "_title" );
		
		// 単価取得
		$unitPrices = get_option('estimate-pattern-setting_print-case');
		// 印刷方法取得
		$printTypeDatas = get_post(177);
		$tempArr = unserialize($printTypeDatas->post_content);
		$datas = [];
		foreach($tempArr['choices'] as $key => $val) {
			$dataPlacementFee = empty($unitPrices[$key]['data_placement_fee']) ? '' : $unitPrices[$key]['data_placement_fee'];
			$printingFee = empty($unitPrices[$key]['printing_fee']) ? '' : $unitPrices[$key]['printing_fee'];
			$printingFeeCustom = empty($unitPrices[$key]['printing_fee_custom']) ? []: $unitPrices[$key]['printing_fee_custom'];
			$datas[$key] = [
				'name' => $val,
				'data_placement_fee' => $dataPlacementFee,
				'printing_fee' => $printingFee,
				'printing_fee_custom' => $printingFeeCustom
			];
		}
		$qStr = explode('&' , $_SERVER['QUERY_STRING']);
		$dispSts = 'display: none';
		$class = '';
		$msg = '';
		if(!empty($qStr[1])) {
			$dispSts = '';
			$param = explode('=' , $qStr[1]);
			switch($param[1]) {
				case 1:
					$class = 'updated';	
					$msg = '保存しました';
					break;
				case 2:
					$class = 'error';
					$msg = '入力形式が誤っているため、保存できませんでした。';
					break;
				case 3:
					$class = 'error';
					$msg = '「○○個以上の印刷個数単価」は、右列がより大きくなるよう数字を入力してください。';
					break;
			}			
		}
		?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.13.0/Sortable.min.js"></script>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<?php wp_enqueue_script ( "jquery" ); ?>
<div class="wrap">
	<h1>印刷方法別単価設定</h1>
	<form action="" method='post' id="my-submenu-form">
		<?php wp_nonce_field ( self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME )?>
		<p><input type='submit' value='保存' class='button button-primary button-large'></p>
		<div id="aiosl_msg_label" class="<?php echo $class; ?>" style="<?php echo $dispSts; ?>"><p><?php echo $msg; ?></p></div>
		<table border="1" bgcolor="white">
			<tbody id="sortable_table">
				<tr class="remove_row">
					<td style="border-style: none;"></td>
					<td style="border-style: none;"></td>
					<td style="border-style: none;"></td>
					<?php $k = array_keys($datas); ?>
					<?php if(isset($datas[$k[0]]['printing_fee_custom'])){ ?>
						<?php foreach($datas[$k[0]]['printing_fee_custom'] as $val) { ?>
							<td style="border-style: none;" align="center"><a href="#" class="remove_btn"><span class="dashicons dashicons-post-trash"></span></a></td>
						<?php } ?>
					<?php } ?>
					<td style="border-style: none;" align="center"></td>
				</tr>
				<tr bgcolor="skyblue">
					<th>印刷方法名</th>
					<th>版代 / データ配置代</th>
					<th>印刷代</th>
					<?php if(isset($datas[$k[0]]['printing_fee_custom'])){ ?>
						<?php foreach($datas[$k[0]]['printing_fee_custom'] as $key => $val) { ?>
							<th><input type="text" size="1" pattern="^(?!0)\d+" title="数字のみ入力できます。０から始まる値は入力できません。" name="printing_fee_custom[<?php echo $key; ?>]" value="<?php echo $key; ?>">個以上の印刷個数単価</th>
						<?php } ?>
					<?php } ?>
					<th><input type="text" size="1" pattern="^(?!0)\d+" title="数字のみ入力できます。０から始まる値は入力できません。" name="new_costom_fee" value="">個以上の印刷個数単価</th>
				</tr>
				<?php foreach ($datas as $idx => $data) { ?>
					<tr class="input_row">
						<input type="hidden" name="datas[<?php echo $idx; ?>][id]" value="<?php echo $idx; ?>">
						<td><input type="text" size="45" name="datas[<?php echo $idx; ?>][name]" value="<?php echo $data['name']; ?>" readonly></td>
						<td><input type="text" pattern="^(?!0)\d+" title="数字のみ入力できます。０から始まる値は入力できません。" name="datas[<?php echo $idx; ?>][data_placement_fee]" value="<?php echo $data['data_placement_fee']; ?>">円</td>
						<td><input type="text" pattern="^(?!0)\d+" title="数字のみ入力できます。０から始まる値は入力できません。" name="datas[<?php echo $idx; ?>][printing_fee]" value="<?php echo $data['printing_fee']; ?>">円</td>
						<?php foreach($datas[$k[0]]['printing_fee_custom'] as $key => $val) { ?>
							<?php $customFee = isset($datas[$idx]['printing_fee_custom'][$key]) ? $datas[$idx]['printing_fee_custom'][$key] : ''; ?>
							<td><input type="text" pattern="^(?!0)\d+" title="数字のみ入力できます。０から始まる値は入力できません。" name="datas[<?php echo $idx; ?>][printing_fee_custom][<?php echo $key; ?>]" value="<?php echo $customFee; ?>">円</td>
						<?php } ?>
						<td><input type="text" pattern="^(?!0)\d+" title="数字のみ入力できます。０から始まる値は入力できません。" name="new_costom_fee_value" value="">円</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<p><input type='submit' value='保存' class='button button-primary button-large'></p>
	</form>
	<table style="display: none;">
		<tr>
			<th class="col-template-th"><input type="text" value=""  size="1">個以上の印刷個数単価</th>
			<td class="col-template-td"><input type="text" value="">円</td>
			<td class="col-template-remove-td" style="border-style: none;" align="center"><a href="#" class="remove_btn"><span class="dashicons dashicons-post-trash"></span></a></td>
		</tr>
	</table>
</div>
<script>
	jQuery(function($) {
		/* 入力済み個数単価変更 */
		$('body').on('change', '#sortable_table th:not(:last-of-type) input', function(){
			let val = $(this).val();
			let idx = $(this).parent().index() + 1;
			$(this).attr('name', "printing_fee_custom[" + val + "]");
			$('#sortable_table td:nth-of-type(' + idx + ') input').each(function(i, elm) {
				let key = $(elm).parents('tr').find('input:hidden').val();
				$(elm).attr('name', "datas[" + key + "][printing_fee_custom][" + val + "]");
			});
		});
		
		/* 空欄列追加 */
		$('body').on('change', '#sortable_table th:last-of-type input', function(){
			if($(this).val()){
				$('.col-template-th').clone().insertAfter('#sortable_table th:last-of-type').removeClass('col-template-th');
				$('.col-template-td').clone().insertAfter('.input_row td:last-of-type').removeClass('col-template-td');
				if ($(this).parent().index() == 3) {
					$('.col-template-remove-td').clone().insertBefore('.remove_row td:last-of-type').removeClass('col-template-remove-td');
				} else {
					$('.col-template-remove-td').clone().insertBefore('.remove_row td:nth-last-of-type(2)').removeClass('col-template-remove-td');
				}
				let val = $(this).val();
				$(this).attr('name', "printing_fee_custom[" + val + "]");
				$('#sortable_table td:nth-last-of-type(2) input').each(function(idx, elm) {
					let key = $(elm).parents('tr').find('input:hidden').val();
					$(elm).attr('name', "datas[" + key + "][printing_fee_custom][" + val + "]");
				});
			}
		});
		
		/* 列削除 */
		$('body').on({
			'mouseenter':function(){
				let idx = $(this).parent().index() + 1;
				$('#sortable_table .remove_row td:nth-of-type(' + idx + ')').css('background-color', '#FFC0CB');
				$('#sortable_table th:nth-of-type(' + idx + ')').css('background-color', '#FFC0CB');
				$('#sortable_table .input_row td:nth-of-type(' + idx + ')').each(function(idx, elm){
					$(elm).css('background-color', '#FFC0CB');
				});
			},'mouseleave':function(){
				let idx = $(this).parent().index() + 1;
				$('#sortable_table .remove_row td:nth-of-type(' + idx + ')').css('background-color', 'white');
				$('#sortable_table th:nth-of-type(' + idx + ')').css('background-color', 'skyblue');
				$('#sortable_table .input_row td:nth-of-type(' + idx + ')').each(function(idx, elm){
					$(elm).css('background-color', 'white');
				});
			}
		}, '#sortable_table .remove_btn');
		$('body').on('click', '#sortable_table .remove_btn', function(){
			let idx = $(this).parent().index() + 1;
			if(confirm('削除しますがよろしいですか？\n※実際に削除するには保存ボタンを押下する必要があります。')){
				$('#sortable_table .remove_row td:nth-of-type(' + idx + ')').remove();
				$('#sortable_table th:nth-of-type(' + idx + ')').remove();
				$('#sortable_table .input_row td:nth-of-type(' + idx + ')').each(function(idx, elm){
					$(elm).remove();
				});
			}
		});
	});
</script>
<?php
	}
}

?>