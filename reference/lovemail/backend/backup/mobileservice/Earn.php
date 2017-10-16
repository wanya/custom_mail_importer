<?php
	// --- Signs --- ;

    function all_task_credit($device, $referCode, $credit, $requestAction, $action, $note)
	{
			tep_db_connect() ;
			input_history($device, $referCode, $action, $note, '+'.$credit);
			update_credit($referCode, $credit);
			echo '{"action":"'.$requestAction.'", "result":"successed","message":"Credited", "device_token":"'.$device.'","device_refer_code":"'.$referCode.'"}' ;
	}





    function daily_check($device, $referCode)
	{
		tep_db_connect() ;
		$date	=	date('Y-m-d');
		$sqlSearch		= "SELECT * FROM tbl_history WHERE device_token = '".$device."' AND device_refer_code = '".$referCode."' AND DATE(credit_date) = '".$date."' AND request_action = 'daily_check'" ;
		$searchResult	= tep_db_query( $sqlSearch ) ;
		if(tep_db_num_rows( $searchResult ) >= 1)
		{
			echo '{"action":"dailyCheck", "result":"failed", "message":"Once a day", "device_token":"'.$device.'", "device_refer_code":"'.$referCode.'"}' ;
		}
		else
		{
			input_history($device, $referCode, 'daily_check', 'Daily check', '+2');
			update_credit($referCode, 2);
			echo '{"action":"dailyCheck", "result":"successed","message":"Credited", "device_token":"'.$device.'","device_refer_code":"'.$referCode.'"}' ;
		}
	}
	
	    function daily_check_validation($device, $referCode)
	{
		tep_db_connect() ;
		$date	=	date('Y-m-d');
		$sqlSearch		= "SELECT * FROM tbl_history WHERE device_token = '".$device."' AND device_refer_code = '".$referCode."' AND DATE(credit_date) = '".$date."' AND request_action = 'daily_check'" ;
		$searchResult	= tep_db_query( $sqlSearch ) ;
		if(tep_db_num_rows( $searchResult ) >= 1)
		{
			echo '{"action":"dailyCheckValidation", "result":"failed", "message":"Once a day", "device_token":"'.$device.'", "device_refer_code":"'.$referCode.'"}' ;
		}
		else
		{
			echo '{"action":"dailyCheckValidation", "result":"successed","message":"Not yet used", "device_token":"'.$device.'","device_refer_code":"'.$referCode.'"}' ;
		}
	}
?>