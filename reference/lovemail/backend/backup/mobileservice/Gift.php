<?php
	// --- Signs --- ;

    function output_gifts()
	{
		tep_db_connect() ;
		$sqlSearch		= "SELECT * FROM tbl_gifts ORDER BY id_gift ASC" ;
		$searchResult	= tep_db_query( $sqlSearch ) ;
		$searchList		= db_result_array( $searchResult ) ;
		echo '{"data":'.json_encode( $searchList ).'}' ;
	}
	
	function claim_validate($device, $referCode, $giftId)
	{
		tep_db_connect() ;
		$deviceResult	= tep_db_query( "SELECT credit_count FROM tbl_referral_code WHERE referral_code = '".$referCode."' and device_token = '".$device."'" ) ;
		$deviceList		= db_result_array( $deviceResult ) ;
		$device_credits	= $deviceList[0]['credit_count'];
		
		$giftResult		= tep_db_query( "SELECT gift_value FROM tbl_gifts WHERE id_gift = '".$giftId."'" ) ;
		$giftList		= db_result_array( $giftResult ) ;
		$claim_credit	= $giftList[0]['gift_value'];
		
		if($device_credits < $claim_credit)
		{
			echo '{"result":"failed","message":"You do not have enough credits for this gift"}' ;
		}
		else
		{
			echo '{"result":"successed","message":"Redirect to claim page"}' ;
		}
	}

    function claim_gift($device, $referCode, $giftId, $emailId)
	{
		if(empty($emailId))
		{
			echo '{"result":"failed","message":"Empty email id"}' ;
		}
		else
		{
			tep_db_connect() ;
			$date		= date('Y-m-d H:i:s');
			$sqlInsert	= "INSERT INTO tbl_claim ( `device_token`, `device_refer_code` ,`id_gift`, `claim_date`, `claim_email` ) VALUES ( '".$device."', '".$referCode."', '".$giftId."', '".$date."', '".$emailId."' )";
			tep_db_query( $sqlInsert ) ;
			
			$giftResult		= tep_db_query( "SELECT * FROM tbl_gifts WHERE id_gift = '".$giftId."'" ) ;
			$giftList		= db_result_array( $giftResult ) ;
			$gift_credit	= $giftList[0]['gift_value'];
			$gift_title		= $giftList[0]['gift_title'];
			$gift_cost		= $giftList[0]['gift_cost'];
			update_credit($referCode, -$gift_credit);
			input_history($device, $referCode, 'claim_action', '$'.$gift_cost.' '.$gift_title.' gift card', '-'.$gift_credit);
			echo '{"result":"successed", "message":"Claimed"}' ;

		}
	}
	
?>