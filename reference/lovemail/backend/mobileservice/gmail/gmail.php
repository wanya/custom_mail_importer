<?php
	require_once 'lib/vendor/autoload.php';
    function getGmailLink()
	{
		global $gmail_client, $gmail_secret, $gmail_uri;
		$client = new Google_Client();
		$client -> setApplicationName('Love ur mail');
		$client -> setClientid($gmail_client);
		$client -> setClientSecret($gmail_secret);
		$client -> setRedirectUri($gmail_uri);
		$client -> setAccessType('offline');
	  	$client->addScope("https://www.googleapis.com/auth/gmail.readonly");
		$googleImportUrl = $client -> createAuthUrl();
		return $googleImportUrl;
	}
	
	function putGmailAuthCode($auth_code)
	{
		global $gmail_client, $gmail_secret, $gmail_uri;
		$client = new Google_Client();
		$client->setClientId($gmail_client);
		$client->setClientSecret($gmail_secret);
		$client->setRedirectUri($gmail_uri);
		$client->addScope("https://www.googleapis.com/auth/gmail.readonly");
		$client->authenticate($auth_code);
		$access_token = $client->getAccessToken();
		return $access_token;
	}
	
	function getGmailMessages($access_token)
	{
		global $gmail_client, $gmail_secret, $gmail_uri;
		$client = new Google_Client();
		$client->setClientId($gmail_client);
		$client->setClientSecret($gmail_secret);
		$client->setRedirectUri($gmail_uri);
		$client->addScope("https://www.googleapis.com/auth/gmail.readonly");
		$service = new Google_Service_Gmail($client);
		$client->setAccessToken($access_token);
		if($client->getAccessToken()) 
		{
			$gmail = $service;
			try 
			{
				$list = $gmail->users_messages->listUsersMessages('me',['maxResults' => 10, 'q' => '']);
				$messageList = $list->getMessages();
				$inboxMessage = [];
				foreach($messageList as $mlist){
					$optParamsGet2['format'] = 'full';
					$single_message = $gmail->users_messages->get('me',$mlist->id, $optParamsGet2);
					$message_id = $mlist->id;
					$headers = $single_message->getPayload()->getHeaders();
					$snippet = $single_message->getSnippet();
					foreach($headers as $single) {

						if ($single->getName() == 'Subject') {

							$message_subject = $single->getValue();

						}

						else if ($single->getName() == 'Date') {

							$message_date = $single->getValue();
							$message_date = date('M jS Y h:i A', strtotime($message_date));
						}

						else if ($single->getName() == 'From') {

							$message_sender = $single->getValue();
							$message_sender = str_replace('"', '', $message_sender);
						}
					}


					 $inboxMessage[] = [
						'messageId' => $message_id,
						'messageSnippet' => $snippet,
						'messageSubject' => $message_subject,
						'messageDate' => $message_date,
						'messageSender' => $message_sender
					];

				}
				$table = "<table border='1'>
							<tr>
								<th>messageId</th>
								<th>messageSnippet</th>
								<th>messageSubject</th>
								<th>messageDate</th>
								<th>messageSender</th>
							</tr>";
					foreach($inboxMessage as $msg)
					{
						$table .= "<tr>
										<td>".$msg['messageId']."</td>
										<td>".$msg['messageSnippet']."</td> 
										<td>".$msg['messageSubject']."</td>
										<td>".$msg['messageDate']."</td>
										<td>".$msg['messageSender']."</td>				
									</tr>";
					}
					$table .= "</table>";
			} 
			catch (Exception $e) 
			{
				print($e->getMessage());
				unset($_SESSION['access_token']);
			}
		} 
 		return $table;
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