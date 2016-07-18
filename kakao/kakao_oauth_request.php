<?php
/**
 * https://developers.kakao.com/apps
 *
 * 앱 -> 설정 -> 웹 플랫폼 추가 -> 사이트 도메인 및 Redirect Path 설정
 *
 * @author nettem
 *
 */
class KakaoOAuthRequest {

    private $client_id;

    private $client_secret;

    function __construct( $client_id, $client_secret, $state_session_id = 'kakao_oauth_state') {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->state_session_id = $state_session_id;
    }

    private function generateState(){
        $mt = microtime();
        $rand = mt_rand();
        return md5( $mt . $rand );
    }

    public function getLoginUrl($redirect_url) {
        $state = $this->generateState();
        $_SESSION[$this->state_session_id] = $state;

        $params = array(
            'client_id'=>$this->client_id,
            'redirect_uri'=>$redirect_url,
            'response_type'=>'code',
            'state'=>$state,
        );

        return 'https://kauth.kakao.com/oauth/authorize'.'?'.http_build_query($params);
    }

    public function getAccesstokenUrl(){

    }

    public function getAccesstoken($code, $state, $redirect_url) {
        if($_SESSION[$this->state_session_id] != $state) throw new Exception('state value fail!');

        $params = array(
            'client_id'=>$this->client_id,
            'client_secret'=>$this->client_secret,
            'redirect_uri'=>$redirect_url,
            'code'=>$code,
            'grant_type'=>'authorization_code',
        );

        $accesstoken_url = 'https://kauth.kakao.com/oauth/token'.'?'.http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $accesstoken_url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_COOKIE, '' );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        $g = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($g, true);

        return array(
            'access_token'=>$data['access_token'],
            'token_type'=>$data['token_type'],
        );
    }

    public function getUserProfile($token_type, $access_token) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://kapi.kakao.com/v1/user/me');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.$token_type.' '.$access_token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_COOKIE, '' );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        $g = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($g, true);
        if($data['code'] != '') throw new Exception($data['code'].' '.$data['msg']);

        return $data;
    }
}
?>