<?php
/**
 * https://developers.facebook.com/apps
 *
 * app 생성
 * 설정->앱 ID, 앱 시크릿 코드
 * Facebook 로그인 -> 클라이언트 OAuth 로그인, 웹 OAuth 로그인, 유효한 OAuth 라디렉션 URI 설정
 *
 * @author nettem
 *
 */
class FacebookOAuthRequest {

    private $client_id;

    private $client_secret;

    private $state_session_id;

    function __construct($client_id, $client_secret, $state_session_id = 'facebook_oauth_state') {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->state_session_id = $state_session_id;
    }

    private function generateState() {
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
            'state'=>$state,
        );

        return 'https://www.facebook.com/dialog/oauth'.'?'.http_build_query($params);
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/oauth/access_token');

        curl_setopt($ch, CURLOPT_POST, 5);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_COOKIE, '' );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        $g = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($g, true);
        if(isset($data['error']) == true) throw new Exception($data['error']['code'].''.$data['error']['message']);

        parse_str($g, $response_params);
        return $response_params['access_token'];
    }


    public function getUserProfile($access_token) {
        $params = array(
            'access_token'=>$access_token,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/me?'.http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_COOKIE, '' );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        $g = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($g, true);
        if(isset($data['error']) == true) throw new Exception($data['error']['code'].''.$data['error']['message']);

        return $data;
    }
}
?>