<?php
	function setRefreshToken($lastId, $email, $access_token, $refreshToken)
	{
		tep_db_connect() ;
		tep_db_query("INSERT INTO `tbl_user_oauth` (`id_account_list`, `oauth_email`, `oauth_access_token`, `oauth_refresh_token` ) VALUES ( '".$lastId."', '".$email."', '".$access_token."', '".$refreshToken."' )") ;		
	}
	
	function updateRefreshToken($email, $access_token)
	{
		tep_db_connect() ;
		tep_db_query("UPDATE tbl_user_oauth SET oauth_access_token = '".$access_token."' WHERE oauth_email='".$email."'") ;		
	}
	
	function getRefreshToken($email)
	{
		tep_db_connect() ;
		$sqlResult		= tep_db_query( "SELECT oauth_refresh_token FROM tbl_user_oauth WHERE oauth_email = '".$email."'" );
		$sqlList		= db_result_array( $sqlResult ) ;
		$refreshToken	= $sqlList[0]['oauth_refresh_token'];
		return $refreshToken;
	}
	
	function getAccessToken($email)
	{
		tep_db_connect() ;
		$sqlResult		= tep_db_query( "SELECT oauth_access_token FROM tbl_user_oauth WHERE oauth_email = '".$email."'" );
		$sqlList		= db_result_array( $sqlResult ) ;
		$accessToken	= $sqlList[0]['oauth_access_token'];
		return $accessToken;
	}


	function getAccounts($deviceToken, $accountType)
	{
		tep_db_connect() ;
		$sqlResult		= tep_db_query( "SELECT account_id FROM tbl_account_list WHERE account_name = '".$accountType."' AND device_id='".$deviceToken."'" );
		$accountList	= db_result_array( $sqlResult ) ;
		return $accountList;
	}

	function setDeviceAccount($deviceId, $email, $accountType)
	{
		tep_db_connect() ;
		tep_db_query("INSERT INTO `tbl_account_list` ( `device_id`, `account_name`, `account_id` ) VALUES ( '".$deviceId."', '".$accountType."', '".$email."' )") ;	
		$insert_id = tep_db_insert_id();
		return $insert_id;	
	}
