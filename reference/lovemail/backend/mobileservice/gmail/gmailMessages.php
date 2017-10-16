<?php
	include('gmailHeader.php');
	if ($client->isAccessTokenExpired()) 
	{
		$refreshToken = getRefreshToken($device_id, $email);
		if(!empty($refreshToken))
		{
			$client->refreshToken($refreshToken);
			$_SESSION[$email]['access_token'] = $client->getAccessToken();
			updateRefreshToken($device_id, $email, $_SESSION[$email]['access_token']['access_token']);  
		}
	}
	
	
	
	if($action =='getGmailMessages')
	{
		if(isset($_SESSION[$email]['access_token'])) 
		{ 
			$client->setAccessToken($_SESSION[$email]['access_token']);
			if($client->getAccessToken()) 
			{		
				try 
				{
					$service	= new Google_Service_Gmail($client);
					$list 		= $service->users_messages->listUsersMessages('me',['maxResults' => 5, 'q' => '']);
					$messageList= $list->getMessages();
					$inboxMessage = [];
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
					unset($_SESSION[$email]['access_token']);
				}
				echo '{"action" : "'.$action.'", "messages" : "'.json_encode($inboxMessage).'"}';
			}
		} 
		else
		{
	
		}
	}
	elseif($action =='gmailMoveMessage')
	{
		if(isset($_SESSION[$email]['access_token'])) 
		{ 
			$client->setAccessToken($_SESSION[$email]['access_token']);
			if($client->getAccessToken()) 
			{	
				$service	= new Google_Service_Gmail($client);
				$mods 		= new Google_Service_Gmail_ModifyMessageRequest();
				$mods->setAddLabelIds('sunu');
				$mods->setRemoveLabelIds('Inbox');
				try 
				{
					$message = $service->users_messages->modify($email, $messageId, $mods);
					print 'Message with ID: ' . $messageId . ' successfully modified.';
				}
				catch (Exception $e)
				{
					print 'An error occurred: ' . $e->getMessage();
				}
			}
		}
	}
?>