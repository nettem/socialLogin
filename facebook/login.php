<?php
error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING));
ini_set ('display_errors', true);

session_start();

require_once(dirname(__FILE__).'/facebook_oauth_request.php');

function siteDomain($prefix = '') {
    //프로토콜
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') $scheme = 'https';

    //2차 도메인
    $server_name = $_SERVER['SERVER_NAME'];
    if($prefix != '') {
        $server_name = preg_split("/\./", $server_name);
        $server_name[0] = $prefix;
        $server_name = implode('.', $server_name);
    }

    //주소
    if($_SERVER['SERVER_PORT'] == 80 || $_SERVER['SERVER_PORT'] == 443) {
        return $scheme.'://'.$server_name;
    } else {
        return $scheme.'://'.$server_name.':'.$_SERVER['SERVER_PORT'];
    }
}

$facebook_oauth_request = new FacebookOAuthRequest('<client_id>', '<client_secret>');



if($_GET['code'] == '') {
    $login_url = $facebook_oauth_request->getLoginUrl(siteDomain().'/facebook/login.php');
    header('Location: '. $login_url);
} else {
    $access_token = $facebook_oauth_request->getAccesstoken($_GET['code'], $_GET['state'], siteDomain().'/facebook/login.php');
    $user_info = $facebook_oauth_request->getUserProfile($access_token);
    var_dump($user_info);
}
?>