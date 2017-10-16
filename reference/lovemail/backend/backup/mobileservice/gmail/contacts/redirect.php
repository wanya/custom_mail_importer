<?php
function curl($url, $post = "") 
{
	$curl = curl_init();
	$userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
	curl_setopt($curl, CURLOPT_URL, $url);
	//The URL to fetch. This can also be set when initializing a session with curl_init().
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	//TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
	//The number of seconds to wait while trying to connect.
	if ($post != "") {
		curl_setopt($curl, CURLOPT_POST, 5);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
	//The contents of the "User-Agent: " header to be used in a HTTP request.
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	//To follow any "Location: " header that the server sends as part of the HTTP header.
	curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
	//To automatically set the Referer: field in requests where it follows a Location: redirect.
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	//The maximum number of seconds to allow cURL functions to execute.
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	//To stop cURL from verifying the peer's certificate.
	$contents = curl_exec($curl);
	curl_close($curl);
	return $contents;
}
$google_client_id 		= '4230135050-fb1p95j2cbl28i07jj9f0erh0p1t4rja.apps.googleusercontent.com';
$google_client_secret 	= 'Bt4p1hE5bPjva-CCjt6e3Vv4';
$google_redirect_uri 	= 'http://loveurmail.com/ryanburch/backend/gmail/contacts/redirect.php';
if (isset($_REQUEST['code'])) 
{
	$auth_code 		= $_REQUEST["code"];
	$max_results 	= 200;
    $fields=array(
        'code'			=>  urlencode($auth_code),
        'client_id'		=>  urlencode($google_client_id),
        'client_secret'	=>  urlencode($google_client_secret),
        'redirect_uri'	=>  urlencode($google_redirect_uri),
        'grant_type'	=>  urlencode('authorization_code')
    );
    $post = '';
    foreach($fields as $key=>$value)
    {
        $post .= $key.'='.$value.'&';
    }
    $post 			= 	rtrim($post,'&');
    $result 		= 	curl('https://accounts.google.com/o/oauth2/token',$post);
    $response 		=  	json_decode($result);
    $accesstoken 	= 	$response->access_token;
    $url 			= 	'https://www.google.com/m8/feeds/contacts/default/full?max-results='.$max_results.'&alt=json&v=3.0&oauth_token='.$accesstoken;
    $xmlresponse 	=  	curl($url);
    $contacts 		= 	json_decode($xmlresponse,true);	
	$return 		= 	array();
	if (!empty($contacts['feed']['entry'])) 
	{
		foreach($contacts['feed']['entry'] as $contact) 
		{
			echo $contact['gd$email'][0]['address'].'<br>';
		}				
	}
}


