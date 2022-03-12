<?php 
    require_once('../functions.php');

    get_header();
    get_sidebar( 'other' );

    $colorNames = $_POST["quantity"];
    $colorDetails = get_post(12);
    $colorDatas = unserialize($colorDetails->post_content);
    foreach($colorDatas['choices'] as $key => $val) {
        if (array_key_exists($key, $colorNames)){
            $colorNames[$key] = [
                'name' => $val,
                'quantity' => $colorNames[$key]
            ];
        }
    }

    $order = $_POST['order'];
    $member = $_POST['member'];
    $nonMember = $_POST['nonMember'];

    $to = $_POST['member']['mailaddress1'];
    $subject = "【ノベルティの秋冬春夏】ご注文を承りました";

    
    $message = $_POST['member']['name1'] . ' ' . $_POST['member']['name2'] . '様' . PHP_EOL . PHP_EOL;
    $message = "この度はご利用頂き、ありがとうございます。" . PHP_EOL;
    $message = "以下の内容でご注文を承りました。" . PHP_EOL. PHP_EOL;
    /* 商品内容 */
    $message .= '【ご注文内容】'. PHP_EOL;
    $message .= '商品名：'. $order['productName'] . PHP_EOL;
    $message .= '色：' . PHP_EOL;
    foreach($colorNames as $val){
        $message .= $val['name'] . '　' . $val['quantity'] . '個' . PHP_EOL;
    }
    $message .= '商品合計：'. $order['totalQuantity'] . '個' . '　×　' . $order['productPrice'] . '円　　　' . $order['totalQuantity'] * $order['productPrice'] . '円' . PHP_EOL;
    $message .= '版代：１版　×　'. $order['dataPlacementFee'] . '円　　　' . $order['dataPlacementFee'] . '円' . PHP_EOL;
    $message .= '印刷代：'. $order['printingFeeQuantity'] . '×' . $order['printingFee']. '円　　　' . $order['printingFeeQuantity'] * $order['printingFee'] . '円' . PHP_EOL;
    $message .= '送料：'. $order['shipmentFee'] . '円' . PHP_EOL;
    $message .= '税抜合計：'. $order['zeinuki'] . '円' . PHP_EOL;
    $message .= '消費税：'. $order['tax'] . '円' . PHP_EOL;
    $message .= '税込合計：'. $order['totalAmount'] . '円' . PHP_EOL;
    $message .= 'ご要望・ご質問：' . PHP_EOL . $_POST['etc'] . PHP_EOL . PHP_EOL . PHP_EOL;
    
    /* 購入者情報 */
    $message .= '【ご購入者様情報】'. PHP_EOL;
    $message .= 'お名前　　　　：'. $member['name1']. $member['name2'] . '様' . PHP_EOL;
    $message .= 'ご住所　　　　：'. $member['pref'] . $member['address1']. $member['address2']. $member['address3']. PHP_EOL;
    $message .= '電話番号　　　：'. $member['tel'] . PHP_EOL;
    $message .= 'ＦＡＸ　　　　：'. $member['fax'] . PHP_EOL;
    $message .= 'メールアドレス：'. $member['mailaddress1'] . PHP_EOL . PHP_EOL . PHP_EOL;
    
    /* 送り先情報 */
    if (!empty($nonMember)){
        $message .= '【お送り先様情報】'. PHP_EOL;
        $message .= 'お名前　　　　：'. $nonMember['name1']. $nonMember['name2'] . '様' . PHP_EOL;
        $message .= 'ご住所　　　　：'. $nonMember['pref'] . $nonMember['address1']. $nonMember['address2']. $nonMember['address3']. PHP_EOL;
        $message .= '電話番号　　　：'. $nonMember['tel'] . PHP_EOL;
        $message .= 'ＦＡＸ　　　　：'. $nonMember['fax'] . PHP_EOL;
        $message .= 'メールアドレス：'. $nonMember['mailaddress1'] . PHP_EOL;
    }

    $headers ="";
    $attachments = "";

    // 購入者向け
    wp_mail( $to, $subject, $message, $headers, $attachments );
    // 管理者向け
    $to = get_option('admin_email');
    wp_mail( $to, $subject, $message, $headers, $attachments ); 
 
    include('./thanks.html');

    get_footer();