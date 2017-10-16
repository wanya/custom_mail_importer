<?php
//function for parsing the curl request
function curl_file_get_contents($url) {
$ch = curl_init();
curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
$data = curl_exec($ch);
curl_close($ch);
return $data;
}
$client_id 		= 	'0000000040194C17';
$client_secret 	= 	'vCbbJYpc1otUYR90UphTMxK0A12IandY';
$redirect_uri 	= 	'http://loveurmail.com/ryanburch/backend/msn/contacts/redirect.php';
$auth_code 		= 	$_GET["code"];
$fields			=	array(
	'code'=>  urlencode($auth_code),
	'client_id'=>  urlencode($client_id),
	'client_secret'=>  urlencode($client_secret),
	'redirect_uri'=>  urlencode($redirect_uri),
	'grant_type'=>  urlencode('authorization_code')
);
$post = '';
foreach($fields as $key=>$value) { $post .= $key.'='.$value.'&'; }
$post = rtrim($post,'&');
$curl = curl_init();
curl_setopt($curl,CURLOPT_URL,'https://login.live.com/oauth20_token.srf');
curl_setopt($curl,CURLOPT_POST,5);
curl_setopt($curl,CURLOPT_POSTFIELDS,$post);
curl_setopt($curl, CURLOPT_RETURNTRANSFER,TRUE);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
$result = curl_exec($curl);
curl_close($curl);
$response =  json_decode($result);
$accesstoken = $response->access_token;
$url = 'https://apis.live.net/v5.0/me/contacts?access_token='.$accesstoken.'&limit=100';
$xmlresponse =  curl_file_get_contents($url);
$xml = json_decode($xmlresponse, true);
$msn_email = "";
foreach($xml['data'] as $emails)
{
// echo $emails['name'];
$email_ids = implode(",",array_unique($emails['emails'])); //will get more email primary,sec etc with comma separate
$msn_email .= "<div><span>".$emails['name']."</span> &nbsp;&nbsp;&nbsp;<span>". rtrim($email_ids,",")."</span></div>";
}
echo $msn_email;
 
?>