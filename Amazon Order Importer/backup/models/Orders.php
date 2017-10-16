<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends CI_Model
{
	function __construct(){

		parent::__construct();
		$this->db = $this->load->database('default', true);
	}

	function insertOrder( $rowData )
	{
		if (empty($rowData) || !count($rowData)) {
			return 0;
		}

		if( $this->getOrderByID( $rowData['OrderID'], $rowData['AddDate']))
			return -1;

		if ($this->db->insert( 'orders', $rowData)) {
			return $this->db->insert_id();
		} else {
			return 0;
		}
	}

	function getOrderByID( $OrderID, $AddDate )
	{
		$this->db->from( "orders" );
		$this->db->where( "OrderID='".$OrderID."' AND AddDate!='".$AddDate."'" );
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$rows = $query->result();
				return $rows[0];
		}

		return NULL;
	}

	function updateError( $OrderID, $Error )
	{
		$this->db->set( "Error", $Error);
		$this->db->where( "OrderID='".$OrderID."'" );

		return $this->db->update('orders');
	}
}

?>
