<?php
	include('gmailHeader.php');
	
	if ($client->isAccessTokenExpired()) 
	{
		$refreshToken = getRefreshToken($device_id, $email);
		if(!empty($refreshToken))
		{
			$client->refreshToken($refreshToken);
			$_SESSION[$email]['access_token'] = $client->getAccessToken();
			$user 	= $oauth->userinfo->get();
			$email 	= $user->email;
			updateRefreshToken($device_id, $email, $_SESSION[$email]['access_token']['access_token']);  
		}
	}
	if(isset($_SESSION[$email]['access_token'])) 
	{ 
		$client->setAccessToken($_SESSION[$email]['access_token']);
		if($client->getAccessToken()) 
		{	
			$service	= new Google_Service_Gmail($client);
			$label 		= new Google_Service_Gmail_Label();
  			$label->setName($labelName);
  			try 
			{
    				$label = $service->users_labels->create($email, $label);
    				print 'Label with ID: ' . $label->getId() . ' created.';
  			}
			catch (Exception $e) 
			{
    			print 'An error occurred: ' . $e->getMessage();
  			}
		}
	}
