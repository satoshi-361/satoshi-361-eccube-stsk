<?php 
    require_once('../functions.php');

    custom_template_redirect();

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
    
    $country = usces_get_base_country();
    $prefs = get_usces_states($country);
    foreach($prefs as $key => $val) {
        if ($val == "--選択--") {
            unset($prefs[$key]);
        }
    }
    
    $member = $_SESSION['usces_member']; 
    $order = $_POST['order'];
    $isConfirmed = false;
    if ($_POST['isModified']) {
        $member = $_POST['member'];
        $nonMember = $_POST['nonMember'];
    }
    if (!empty($errMsg)) {
        $member = $_POST['member'];
        $nonMember = $_POST['nonMember'];
    }
    $dispStatus = 'style="display: none;"';
    
    include('./order.html');

    get_footer();