<?php
/**
 * https://console.developers.google.com/
 *
 * API 관리자 -> 사용자 인증 정보
 * 웹 클라이언트 생성 -> 클라이언트 ID, 클라이언트 보안 비밀번호
 * 승인된 라디렉션 URI 설정
 *
 * @author nettem
 *
 */
class GoogleOAuthRequest {

    private $client_id;

    private $client_secret;

    private $state_session_id;

    function __construct($client_id, $client_secret, $state_session_id = 'google_oauth_state') {
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
            'scope'=>'https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.profile.emails.read',
            'state'=>$state,
            'response_type'=>'code',
            'approval_prompt'=>'force',
            'access_type'=>'offline',
        );

        return 'https://accounts.google.com/o/oauth2/auth'.'?'.http_build_query($params);
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
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v4/token');

        curl_setopt($ch, CURLOPT_POST, 5);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

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
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.$token_type.' '.$access_token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_COOKIE, '' );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        $g = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($g, true);
        if(isset($data['error']) == true) throw new Exception($data['error']['errors'][0]['code'].''.$data['error']['errors'][0]['message']);

        return $data;
    }
}
?>