<?php
	// --- Signs --- ;

	function start()
	{
		// Parameters ;
		$deviceToken	= tep_get_value_require( "deviceToken" ) ;
        $referCode		= tep_get_value_require( "referalCode" ) ;
		$date			= tep_now_datetime() ;
		tep_db_connect() ;
		if(empty($referCode))
		{
			if(validate_device($deviceToken) == 1)
			{
				$searchList					= get_device_datas($deviceToken);
				$return_array 				= $searchList[0];
				$return_array['result'] 	= 'successed';
				$return_array['message'] 	= 'Exist referral code';
				$return_array['action'] 	= 'start';
				echo json_encode($return_array) ;
			}
			else
			{
				$referCode	= generateReferelcode(5);
				$sqlInsert	= "INSERT INTO `tbl_referral_code` ( `referral_code`, `device_token`, `createAt` ) VALUES ( '".$referCode."', '".$deviceToken."', '".$date."' )";
				tep_db_query( $sqlInsert ) ;
				$sqlInsert	= "INSERT INTO `tbl_devices` ( `device_token`, `device_refered_by`, `date` ) VALUES ( '".$deviceToken."', '".$referCode."', '".$date."' )";
				tep_db_query( $sqlInsert ) ;
				$searchList				= get_device_datas($deviceToken, $referCode);
				$return_array 			= $searchList[0];
				$return_array['result'] = 'successed';
				$return_array['message']= 'New Device';
				$return_array['action'] = 'start';
				echo json_encode($return_array) ;
			}
		}
		else
		{
			if(validate_ref_code($referCode) == 0)
			{
				echo '{"result":"failed","message":"Invalid referal code"}' ;
			}
			elseif(validate_device($deviceToken) == 1)
			{
				$searchList					= get_device_datas($deviceToken, $referCode);
				$return_array 				= $searchList[0];
				$return_array['result'] 	= 'successed';
				$return_array['message'] 	= 'Exist referral code';
				$return_array['action'] 	= 'start';
				echo json_encode($return_array) ;
			}
			else
			{
				$sqlInsert	= "INSERT INTO `tbl_devices` ( `device_token`, `device_refered_by`, `date` ) VALUES ( '".$deviceToken."', '".$referCode."', '".$date."' )";
				tep_db_query( $sqlInsert ) ;
				input_history($deviceToken, $referCode, "referral_start", "Referried Friend", '+25');
				update_credit($referCode, 25);
				$searchList				= get_device_datas($deviceToken, $referCode);
				$return_array 			= $searchList[0];
				$return_array['result'] = 'successed';
				$return_array['message']= 'New device with referal code';
				$return_array['action'] = 'start';
				echo json_encode($return_array) ;
			}
		}
		tep_db_close();
    }    

    function validate_ref_code($referCode)
	{
		$validateQuery		= "SELECT * from tbl_referral_code WHERE referral_code = '".$referCode."'";
		$validateResult		= tep_db_query( $validateQuery ) ;
		if(tep_db_num_rows( $validateResult ) == 1)
			return 1;
		else
			return 0;
	}
	
	function validate_device($deviceToken)
	{
		$validateQuery		= "SELECT * from tbl_referral_code WHERE device_token = '".$deviceToken."'";
		$validateResult		= tep_db_query( $validateQuery ) ;
		if(tep_db_num_rows( $validateResult ) == 1)
			return 1;
		else
			return 0;
	}

	function generateReferelcode($length = 5) {
		$characters 		= '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength 	= strlen($characters);
		$randomString 		= '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		if(validate_ref_code($randomString))
			$randomString = generateReferelcode(5);
		else
			return $randomString;
	}
		?>