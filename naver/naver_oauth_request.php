<?php

/**
 * https://developers.naver.com/openapi
 *
 * 내 어플리케이션
 * ClientID, ClientSecret
 * 설정에서 CallbackURL 설정
 * @author nettem
 *
 */
class NaverOAuthRequest {

    private $client_id;

    private $client_secret;

    private $state_session_id;

    function __construct($client_id, $client_secret, $state_session_id = 'naver_oauth_state') {
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
            'response_type'=>'code',
            'state'=>$state,
        );

        return 'https://nid.naver.com/oauth2.0/authorize'.'?'.http_build_query($params);
    }

    public function getAccesstoken($code, $state, $redirect_url) {
        if($_SESSION[$this->state_session_id] != $state) throw new Exception('state value fail!');

        $params = array(
            'client_id'=>$this->client_id,
            'client_secret'=>$this->client_secret,
            //'redirect_uri'=>$redirect_url,
            'code'=>$code,
            'grant_type'=>'authorization_code',
        );

        $accesstoken_url = 'https://nid.naver.com/oauth2.0/token'.'?'.http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $accesstoken_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_COOKIE, '' );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        $g = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($g, true);
        if($data['error'] != '') throw new Exception($data['error'].' '.$data['error_description']);

        return array(
            'access_token'=>$data['access_token'],
            'token_type'=>$data['token_type'],
        );
    }

    public function getUserProfile($token_type, $access_token) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://apis.naver.com/nidlogin/nid/getUserProfile.xml');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.$token_type.' '.$access_token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_COOKIE, '' );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        $g = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($g);
        if($xml->result->resultcode != '00') throw new Exception($xml->result->resultcode.' '.$xml->result->message);

        return array(
            'userID' => explode("@", (string)$xml->response->email )[0],
            'nickname' => (string)$xml->response->nickname,
            'age' => (string)$xml->response->age,
            'birth' => (string)$xml->response->birthday,
            'gender' => (string)$xml->response->gender,
            'profImg' => (string)$xml->response->profile_image
        );
    }
}
?>