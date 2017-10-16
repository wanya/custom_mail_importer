<?php
  session_start();
  require_once('oauth.php');
  $auth_code 	= $_GET['code'];
  $redirectUri 	= 'https://loveurmail.com/ryanburch/backend/mobileservice/msn/authorize.php';
  $tokens 		= oAuthService::getTokenFromAuthCode($auth_code, $redirectUri);

  print_r($tokens);
  if ($tokens['access_token']) {
    $_SESSION['access_token'] = $tokens['access_token'];

    // Get the user's email from the ID token
	$user_email = oAuthService::getUserEmailFromIdToken($tokens['id_token']);
	$_SESSION['user_email'] = $user_email;

    // Redirect back to home page
   // header("Location: http://loveurmail.com/ryanburch/backend/mobileservice/msn/home.php");
  }
  else
  {
    echo "<p>ERROR: ".$tokens['error']."</p>";
  }
?>