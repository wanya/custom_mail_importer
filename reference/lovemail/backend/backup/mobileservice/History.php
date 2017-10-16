<?php
	// --- Signs --- ;

	function input_history($deviceCode, $referCode, $action, $note, $point)
	{
		$date		= 	tep_now_datetime() ;
		tep_db_connect() ;
		$sqlInsert	= 	"INSERT INTO `tbl_history` ( `device_token`, `device_refer_code`, `request_action`, `history_note`, `credit_earned`, `credit_date` ) VALUES ( '".$deviceCode."', '".$referCode."', '".$action."', '".$note."', '".$point."', '".$date."' )";
		tep_db_query( $sqlInsert ) ;
    }    

    function output_history($deviceCode, $referCode)
	{
		tep_db_connect() ;
		$sqlSearch		= "SELECT * FROM tbl_history WHERE device_refer_code = '".$referCode."' AND device_token = '".$deviceCode."' ORDER BY credit_date DESC" ;
		$searchResult	= tep_db_query( $sqlSearch ) ;
		$searchList		= db_result_array( $searchResult ) ;
		$return_array['action'] 	= 'showHistory';
		$return_array['data'] 		= $searchList;
		echo json_encode( $return_array );
	}
	
?>