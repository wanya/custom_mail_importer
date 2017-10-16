<?php
    function review_check($device, $referCode, $page)
	{
		tep_db_connect() ;
		$sqlSearch		= "SELECT id_review FROM tbl_review WHERE review_page = '".$page."' AND review_status = '1'" ;
		$searchResult	= tep_db_query( $sqlSearch ) ;
		if(tep_db_num_rows( $searchResult ) == 1)
		{
			$searchList			= 	db_result_array( $searchResult ) ;
			$id_review			=	$searchList[0]['id_review'];
			
			$historySearch		= 	"SELECT * FROM tbl_review_history WHERE device_token = '".$device."' AND device_refer_code = '".$referCode."' AND review_id = '".$id_review."'" ;
			$historyResult		= 	tep_db_query( $historySearch ) ;
			if(tep_db_num_rows( $historyResult ) < 1)
			{
				$sqlSearch		= 	"SELECT * FROM tbl_review WHERE id_review = '".$id_review."'" ;
				$searchResult	= 	tep_db_query( $sqlSearch ) ;
				$searchList		= 	db_result_array( $searchResult ) ;
				
				$return_array['result'] 			= 'successed';
				$return_array['action'] 			= 'getReviewaction';
				$return_array['device_token'] 		= $device;
				$return_array['device_refer_code'] 	= $referCode;
				$return_array['reviewTitle'] 		= $searchList[0]['review_title'];
				$return_array['reivewDescription'] 	= $searchList[0]['review_description'];
				$return_array['reviewCredit'] 		= $searchList[0]['review_credit'];
				$return_array['reviewLink'] 		= $searchList[0]['review_link'];
				$return_array['reviewId'] 			= $searchList[0]['id_review'];
				$return_array['reviewActiveFlag'] 	= 1;
				
				echo json_encode($return_array) ;
			}
			else
			{
				echo '{"action":"getReviewaction", "result":"failed", "device_token":"'.$device.'", "device_refer_code":"'.$referCode.'", "reviewActiveFlag":"0"}' ;
			}
		}
		else
		{
			echo '{"action":"getReviewaction", "result":"failed", "device_token":"'.$device.'", "device_refer_code":"'.$referCode.'", "reviewActiveFlag":"0"}' ;
		}
	}
	function input_review_history($device, $referCode, $review_id, $credit)
	{
		$date		= 	tep_now_datetime() ;
		tep_db_connect() ;
		
		$sqlSearch		= 	"SELECT * FROM tbl_review_history WHERE device_token = '".$device."' AND device_refer_code = '".$referCode."' AND review_id = '".$review_id."'" ;
		$searchResult	= tep_db_query( $sqlSearch ) ;
		if(tep_db_num_rows( $searchResult ) < 1)
		{
			$sqlInsert	= 	"INSERT INTO `tbl_review_history` ( `device_token`, `device_refer_code`, `review_id`, `review_date` ) VALUES ( '".$device."', '".$referCode."', '".$review_id."', '".$date."' )";
			tep_db_query( $sqlInsert ) ;
			all_task_credit($device, $referCode, $credit, 'reviewSubmit', 'review_submit_action', 'Review Submited');
		}
		else
		{
			echo '{"action":"reviewSubmit", "result":"failed", "message":"Already reviewed this one", "device_token":"'.$device.'", "device_refer_code":"'.$referCode.'"}' ;
		}
    }    

?>