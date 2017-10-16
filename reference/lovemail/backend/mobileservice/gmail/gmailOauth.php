<?php
	include('gmailHeader.php');
	session_start();
	if(isset($code))
	{
		$client->authenticate($code);
		$token  		= $client->getAccessToken();
		$refreshToken 	= $client->getRefreshToken();	
		$user 	= $oauth->userinfo->get();
		$email 	= $user->email;
		$_SESSION[$email]['access_token'] = $token;
		setDeviceAccount($_SESSION['device_id'], $email, 'gmail');
		setRefreshToken($_SESSION['device_id'], $email, $_SESSION[$email]['access_token']['access_token'], $refreshToken);   
		header('Location: ' . filter_var('http://loveurmail.com/ryanburch/backend/index.php?action=getGmailMessages&email='.$email.'&deviceId='.$_SESSION['device_id'], FILTER_SANITIZE_URL));
		unset($_SESSION['device_id']);
	}
	else
	{
		$authUrl 				= $client->createAuthUrl();
		$_SESSION['device_id']	= $device_id;
		echo '{"action" : "'.$action.'", "url" : "'.$authUrl.'"}';
	}
?>	
