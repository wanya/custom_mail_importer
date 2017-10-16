<?php
 	require_once('lib/vendor/autoload.php');
	include('gmailFunctions.php');
  	$client_id     = '4230135050-fb1p95j2cbl28i07jj9f0erh0p1t4rja.apps.googleusercontent.com';
  	$client_secret = 'Bt4p1hE5bPjva-CCjt6e3Vv4';
  	$redirect_uri  = 'http://loveurmail.com/ryanburch/backend/index.php?account=gmail';
	$accountList = getAccounts($device_id, 'gmail');
	if(count($accountList) > 0)
	{
		$inboxMessage = [];
		foreach($accountList as $account)
		{
			foreach($account as $email)
			{
				$client = new Google_Client();
				$client->setClientId($client_id);
				$client->setClientSecret($client_secret);
				$client->setRedirectUri($redirect_uri);
				$client->addScope("https://www.googleapis.com/auth/gmail.modify");
				$client->addScope("https://www.googleapis.com/auth/userinfo.profile");
				$client->addScope("https://www.googleapis.com/auth/userinfo.email");
				$client->setApprovalPrompt('force');	
				$client->setAccessType('offline');
				$oauth 		= new Google_Service_Oauth2($client);
				if ($client->isAccessTokenExpired()) 
				{
					$refreshToken = getRefreshToken($device_id, $email);
					if(!empty($refreshToken))
					{
						$client->refreshToken($refreshToken);
					}
				}
				if($client->getAccessToken()) 
				{		
					try 
					{
						$service		= new Google_Service_Gmail($client);
						$list 			= $service->users_messages->listUsersMessages('me',['maxResults' => 10, 'q' => '']);
						$messageList 	= $list->getMessages();
						foreach($messageList as $mlist){
							$optParamsGet2['format'] = 'full';
							$single_message = $service->users_messages->get('me',$mlist->id, $optParamsGet2);
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
		
							 $inboxMessage[$email][] = [
								'messageId' => $message_id,
								'messageSnippet' => $snippet,
								'messageSubject' => $message_subject,
								'messageDate' => $message_date,
								'messageSender' => $message_sender
							];
						}
					} 
					catch (Exception $e) 
					{
						print($e->getMessage());
					}
				}				
			}
		}
		echo '{"action" : "'.$action.'", "messages" : "'.json_encode($inboxMessage).'"}'; ;
	}
	else
	{
		echo '{"action" : "'.$action.'", "error" : "No accounts found"}';
	}