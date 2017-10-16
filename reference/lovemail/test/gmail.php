<?php
	require_once 'lib/vendor/autoload.php';
	
    function getGmailLink($id, $secret, $uri)
	{
		$client = new Google_Client();
		$client -> setApplicationName('Love ur mail');
		$client -> setClientid($id);
		$client -> setClientSecret($secret);
		$client -> setRedirectUri($uri);
		$client -> setAccessType('online');
		$client -> setScopes('https://www.google.com/m8/feeds');
		$googleImportUrl = $client -> createAuthUrl();
		return $googleImportUrl;
	}
	
    function getGmailContacts($id, $secret, $uri, $auth_code)
	{
		$max_results 	= 200;
		$fields=array(
			'code'			=>  urlencode($auth_code),
			'client_id'		=>  urlencode($id),
			'client_secret'	=>  urlencode($secret),
			'redirect_uri'	=>  urlencode($uri),
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
	
    function getGmailMessages($username, $password, $imapPath, $label)
	{
		set_time_limit(4000); 
		$inbox 	= imap_open($imapPath,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());
		$emails = imap_search($inbox, $label);
		$output = '';
		
		foreach($emails as $mail) 
		{
			$headerInfo = imap_headerinfo($inbox,$mail);
			$output .= $headerInfo->subject.'<br/>';
			$output .= $headerInfo->toaddress.'<br/>';
			$output .= $headerInfo->date.'<br/>';
			$output .= $headerInfo->fromaddress.'<br/>';
			$output .= $headerInfo->reply_toaddress.'<br/>';
			
			$emailStructure = imap_fetchstructure($inbox,$mail);
			if(!isset($emailStructure->parts)) 
			{
				 $output .= imap_body($inbox, $mail, FT_PEEK);
			} 
		    echo $output;
		    $output = '';
		}
		imap_expunge($inbox);
		imap_close($inbox);
	}
	
	function curl($url, $post = "") 
	{
		$curl = curl_init();
		$userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		if ($post != "") {
			curl_setopt($curl, CURLOPT_POST, 5);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		$contents = curl_exec($curl);
		curl_close($curl);
		return $contents;
	}

?>