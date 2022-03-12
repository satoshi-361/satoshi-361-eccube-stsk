<?php 
    require_once('../functions.php');

    /* 入力チェック */
    $checkArr = [
        'name1',
        'name2',
        'address1',
        'address2',
        'pref',
        'zipcode',
        'tel',
        'mailaddress1'
    ];
    $result = aiosl_validation($_POST['member'], $checkArr);
    $_POST['member'] = $result['target'];
    $errMsg = $result['errMsg'];
    if ($_POST["otherAddress"] == 'on') {
        $checkArrNonMember = [
            'nonMember_name1',
            'nonMember_name2',
            'nonMember_address1',
            'nonMember_address2',
            'nonMember_pref',
            'nonMember_zipcode',
            'nonMember_tel'
        ];
        $result = aiosl_validation($_POST['nonMember'], $checkArrNonMember);
        $_POST['nonMember'] = $result['target'];
        $errMsg += $result['errMsg'];
    }
	if (!empty($errMsg)) {
		$url = plugins_url() . '/estimate-pattern-setting/template/order.php';
        $otherAddress = 'on';
		require_once('./order.php');
	}


    /* 確認ページ表示 */
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

    $dispStatus = '';
    $order = $_POST['order'];
    $member = $_POST['member'];
    $nonMember = $_POST['nonMember']; 
    $etc = $_POST['etc'];
    $isConfirmed = true;
    
    include('./confirmation.html');

    get_footer();