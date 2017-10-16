<?php
    
	function get_device_by_refer_code($referCode)
	{
		tep_db_connect() ;
		$sqlSearch		= "select device_refer_code from tbl_devices where `device_token` = '".$device."'" ;
		$searchResult	= tep_db_query( $sqlSearch ) ;
		$searchList		= db_result_array( $searchResult ) ;
		return 	$searchList[0]['device_refer_code'];
	}



/*newwwwwwwwwwwwww*/

	function update_credit($referCode, $credit)
	{
		tep_db_connect() ;
		$sqlUpdate	= "UPDATE tbl_referral_code SET credit_count = credit_count+".$credit." WHERE referral_code = '".$referCode."'" ;
		tep_db_query( $sqlUpdate ) ;
	}

	function get_credit($referCode)
	{
		tep_db_connect() ;
		$sqlSearch		= "select credit_count, lucky_tap from tbl_referral_code WHERE referral_code = '".$referCode."'" ;
		$searchResult	= tep_db_query( $sqlSearch ) ;
		$searchList		= db_result_array( $searchResult ) ;
		
		return 	array($searchList[0]['credit_count'], $searchList[0]['lucky_tap']);
	}


	function get_device_datas($deviceCode, $referCode = '')
	{
		tep_db_connect() ;
		$qry = '';
		if(!empty($referCode))
		{
			$qry = " AND device_refered_by = '".$referCode."'";
		}
		$sqlSearch		= "select * from tbl_devices where `device_token` = '".$deviceCode."'".$qry;
		$searchResult	= tep_db_query( $sqlSearch ) ;
		$searchList		= db_result_array( $searchResult ) ;
		return 	$searchList;
	}

?>