<?php
$security_token = "4a71fa775cdcc068540b893ccccb34b0";
$amount 		= $_GET['amount'];
$referalCode 	= $_GET['uid'];
$transid 		= $_GET['_trans_id_'];
$device			= '3EEB6CEA-C514-4327-8A6E-5CE4DA17BB60';//$_GET['pub0'];
$sha1_of_important_data = sha1($security_token . $referalCode . $amount . $transid);

if ( $_GET['sid'] == $sha1_of_important_data ) 
{
	require( '../includes/application.php' ) ;
	all_task_credit($device, $referalCode, (int)$amount, 'fyberTask', 'fyber_tasks_action', 'Completed Fyber Task');
}
else
{
	echo '{"action":"fyberTask", "message":"failed"}'; 
}
?>  
