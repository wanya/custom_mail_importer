<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Items extends CI_Model
{
	function __construct(){

		parent::__construct();
		$this->db = $this->load->database('default', true);
	}

	function insertItem( $rowData )
	{
		if (empty($rowData) || !count($rowData)) {
			return 0;
		}

		if ( $this->db->insert( 'order_items', $rowData) ) {
			return $this->db->insert_id();
		} else {
			return 0;
		}
	}
	
	function getItemsByOrder( $OrderID )
	{
		$this->db->from( "order_items" );
		$this->db->where( "OrderID='".$OrderID."'" );
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$rows = $query->result();
				return $rows;
		}

		return NULL;
	}

}

?>
