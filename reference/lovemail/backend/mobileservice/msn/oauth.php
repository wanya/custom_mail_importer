<?php
class oAuthService {
    private static $clientId 		= "4ca8fd94-db42-4ac8-b1eb-b189345e72f6";
    private static $clientSecret 	= "2Ot1PWdU4h5ygZVjVZNyz1e";
    private static $authority 		= "https://login.microsoftonline.com";
    private static $authorizeUrl 	= '/common/oauth2/v2.0/authorize?client_id=%1$s&redirect_uri=%2$s&response_type=code&scope=%3$s';
    private static $tokenUrl 		= "/common/oauth2/v2.0/token";
    private static $scopes 			= array("openid", "https://outlook.office.com/mail.read", "offline_access");

    public static function getLoginUrl($redirectUri) 
	{
      $scopestr = implode(" ", self::$scopes);
      $loginUrl = self::$authority.sprintf(self::$authorizeUrl, self::$clientId, urlencode($redirectUri), urlencode($scopestr));
      error_log("Generated login URL: ".$loginUrl);
      return $loginUrl;
    }
	
	public static function getTokenFromAuthCode($authCode, $redirectUri) 
	{
		$token_request_data = array(
			"grant_type" 	=> "authorization_code",
			"code" 			=> $authCode,
			"redirect_uri" 	=> $redirectUri,
			"scope" 		=> implode(" ", self::$scopes),
			"client_id" 	=> self::$clientId,
			"client_secret" => self::$clientSecret
		);

		$token_request_body = http_build_query($token_request_data);
		error_log("Request body: ".$token_request_body);

		$curl = curl_init(self::$authority.self::$tokenUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $token_request_body);

		$response = curl_exec($curl);
		error_log("curl_exec done.");

		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		error_log("Request returned status ".$httpCode);
		if ($httpCode >= 400) 
		{
			return array('errorNumber' => $httpCode, 'error' => 'Token request returned HTTP error '.$httpCode);
		}

		$curl_errno 	= curl_errno($curl);
		$curl_err 		= curl_error($curl);
		if ($curl_errno) 
		{
			$msg 	= $curl_errno.": ".$curl_err;
			error_log("CURL returned an error: ".$msg);
			return array('errorNumber' => $curl_errno, 'error' => $msg);
		}

		curl_close($curl);

		$json_vals 	= json_decode($response, true);
		error_log("TOKEN RESPONSE:");
		foreach ($json_vals as $key=>$value) 
		{
			error_log("  ".$key.": ".$value);
		}
		return $json_vals;
	}	
	
	public static function getUserEmailFromIdToken($idToken) 
	{
		error_log("ID TOKEN: ".$idToken);
		$token_parts 	= explode(".", $idToken);
		$token 			= strtr($token_parts[1], "-_", "+/");
		$jwt 			= base64_decode($token);
		$json_token 	= json_decode($jwt, true);
		return $json_token['preferred_username'];
	}	
}
?>